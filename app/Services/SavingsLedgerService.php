<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SavingsLedgerService
{
    public function record(
        Customer $customer,
        User $admin,
        string $type,
        string $category,
        string $amount,
        string $idempotencyKey,
        ?string $note = null,
    ): SavingsTransaction {
        $amountMinor = $this->toMinor($amount);
        if ($amountMinor < 1) {
            throw ValidationException::withMessages(['amount' => 'Nominal harus lebih besar dari nol.']);
        }

        return DB::transaction(function () use ($customer, $admin, $type, $category, $amountMinor, $idempotencyKey, $note) {
            $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $existing = SavingsTransaction::withTrashed()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                if ($existing->trashed()) {
                    throw ValidationException::withMessages(['amount' => 'Transaksi dengan kunci idempotensi ini sudah dihapus.']);
                }

                if ((int) $existing->customer_id === (int) $lockedCustomer->id
                    && $existing->type === $type
                    && $existing->category === $category
                    && $this->toMinor($existing->amount) === $amountMinor
                    && $existing->note === $note) {
                    return $existing;
                }

                throw ValidationException::withMessages(['amount' => 'Kunci idempotensi sudah digunakan untuk transaksi lain.']);
            }

            if ($type === 'DEPOSIT' && $lockedCustomer->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['type' => 'Setoran hanya dapat dicatat untuk anggota aktif.']);
            }

            $lastTransaction = $lockedCustomer->savingsTransactions()->orderByDesc('id')->first();
            $currentBalanceMinor = $lastTransaction ? $this->toMinor($lastTransaction->balance_after) : 0;

            $currentCategoryBalanceMinor = $this->categoryBalanceMinor($lockedCustomer, $category);
            if ($type === 'WITHDRAWAL' && $amountMinor > $currentCategoryBalanceMinor) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo tabungan '.strtolower($category).' tidak mencukupi untuk penarikan ini.',
                ]);
            }

            if ($type === 'WITHDRAWAL' && $amountMinor > $currentBalanceMinor) {
                throw ValidationException::withMessages(['amount' => 'Saldo tabungan tidak mencukupi untuk penarikan ini.']);
            }

            $balanceAfterMinor = $type === 'DEPOSIT'
                ? $currentBalanceMinor + $amountMinor
                : $currentBalanceMinor - $amountMinor;

            return $lockedCustomer->savingsTransactions()->create([
                'recorded_by' => $admin->id,
                'type' => $type,
                'category' => $category,
                'amount' => $this->fromMinor($amountMinor),
                'balance_after' => $this->fromMinor($balanceAfterMinor),
                'idempotency_key' => $idempotencyKey,
                'reference_code' => 'SAV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'note' => $note,
            ]);
        }, attempts: 3);
    }

    public function update(
        Customer $customer,
        int $transactionId,
        User $admin,
        string $type,
        string $category,
        string $amount,
        ?string $note = null,
    ): SavingsTransaction {
        $amountMinor = $this->toMinor($amount);
        if ($amountMinor < 1) {
            throw ValidationException::withMessages(['amount' => 'Nominal harus lebih besar dari nol.']);
        }

        return DB::transaction(function () use ($customer, $transactionId, $admin, $type, $category, $amountMinor, $note): SavingsTransaction {
            $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $transaction = $lockedCustomer->savingsTransactions()->whereKey($transactionId)->lockForUpdate()->firstOrFail();
            $transaction->forceFill([
                'type' => $type,
                'category' => $category,
                'amount' => $this->fromMinor($amountMinor),
                'note' => $note,
                'updated_by' => $admin->id,
            ])->save();

            $this->rebuildBalances($lockedCustomer, $admin->id);

            return $transaction->refresh();
        }, attempts: 3);
    }

    /** @param array<int, int> $transactionIds */
    public function deleteMany(Customer $customer, array $transactionIds, User $admin): int
    {
        return DB::transaction(function () use ($customer, $transactionIds, $admin): int {
            $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $transactions = $lockedCustomer->savingsTransactions()
                ->whereIn('id', $transactionIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($transactions->count() !== count($transactionIds)) {
                throw ValidationException::withMessages(['ids' => 'Satu atau beberapa transaksi sudah tidak tersedia. Muat ulang riwayat lalu coba lagi.']);
            }

            foreach ($transactions as $transaction) {
                $transaction->forceFill(['deleted_by' => $admin->id])->save();
                $transaction->delete();
            }

            $this->rebuildBalances($lockedCustomer, $admin->id);

            return $transactions->count();
        }, attempts: 3);
    }

    private function rebuildBalances(Customer $customer, int $updatedBy): void
    {
        $balanceMinor = 0;
        $categoryBalances = ['POKOK' => 0, 'WAJIB' => 0, 'SUKARELA' => 0];
        $transactions = $customer->savingsTransactions()->orderBy('id')->lockForUpdate()->get();

        foreach ($transactions as $transaction) {
            $amountMinor = $this->toMinor((string) $transaction->amount);
            $change = $transaction->type === 'DEPOSIT' ? $amountMinor : -$amountMinor;
            $balanceMinor += $change;

            if ($balanceMinor < 0) {
                throw ValidationException::withMessages([
                    'transaction' => "Perubahan dibatalkan karena transaksi {$transaction->reference_code} membuat saldo keseluruhan menjadi negatif.",
                ]);
            }

            if ($transaction->category !== null && array_key_exists($transaction->category, $categoryBalances)) {
                $categoryBalances[$transaction->category] += $change;

                if ($categoryBalances[$transaction->category] < 0) {
                    throw ValidationException::withMessages([
                        'transaction' => "Perubahan dibatalkan karena transaksi {$transaction->reference_code} membuat saldo tabungan {$transaction->category} menjadi negatif.",
                    ]);
                }
            }

            $newBalance = $this->fromMinor($balanceMinor);
            if ($this->toMinor((string) $transaction->balance_after) !== $balanceMinor) {
                $transaction->forceFill([
                    'balance_after' => $newBalance,
                    'updated_by' => $updatedBy,
                ])->save();
            }
        }
    }

    public function categoryTotals(Customer $customer): array
    {
        $totals = ['POKOK' => 0, 'WAJIB' => 0, 'SUKARELA' => 0];

        $customer->savingsTransactions()
            ->whereNotNull('category')
            ->get(['category', 'type', 'amount'])
            ->each(function (SavingsTransaction $transaction) use (&$totals): void {
                if (!array_key_exists($transaction->category, $totals)) {
                    return;
                }

                $amountMinor = $this->toMinor($transaction->amount);
                $totals[$transaction->category] += $transaction->type === 'DEPOSIT' ? $amountMinor : -$amountMinor;
            });

        return array_map(fn (int $minor): string => $this->fromMinor($minor), $totals);
    }

    /**
     * Return the category balance after each transaction in a newest-first page.
     * The query is limited to transactions up to the newest item on that page.
     *
     * @return array<int, string>
     */
    public function categoryBalancesForDescendingPage(Customer $customer, string $category, Collection $transactions): array
    {
        $newestTransaction = $transactions->first();
        if (!$newestTransaction) {
            return [];
        }

        $runningBalance = $customer->savingsTransactions()
            ->where('category', $category)
            ->where('id', '<=', $newestTransaction->id)
            ->get(['type', 'amount'])
            ->sum(fn (SavingsTransaction $transaction): int => $transaction->type === 'DEPOSIT'
                ? $this->toMinor($transaction->amount)
                : -$this->toMinor($transaction->amount));

        $balances = [];
        foreach ($transactions as $transaction) {
            $balances[$transaction->id] = $this->fromMinor($runningBalance);
            $amountMinor = $this->toMinor($transaction->amount);
            $runningBalance += $transaction->type === 'DEPOSIT' ? -$amountMinor : $amountMinor;
        }

        return $balances;
    }

    private function categoryBalanceMinor(Customer $customer, string $category): int
    {
        return $customer->savingsTransactions()
            ->where('category', $category)
            ->get(['type', 'amount'])
            ->sum(fn (SavingsTransaction $transaction): int => $transaction->type === 'DEPOSIT'
                ? $this->toMinor($transaction->amount)
                : -$this->toMinor($transaction->amount));
    }

    public function toMinor(string $amount): int
    {
        if (!preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D', $amount)) {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function fromMinor(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
