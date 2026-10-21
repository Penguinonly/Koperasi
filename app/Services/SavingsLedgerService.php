<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SavingsTransaction;
use App\Models\User;
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
            $existing = SavingsTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
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

    private function categoryBalanceMinor(Customer $customer, string $category): int
    {
        return $customer->savingsTransactions()
            ->where('category', $category)
            ->get(['type', 'amount'])
            ->sum(fn (SavingsTransaction $transaction): int => $transaction->type === 'DEPOSIT'
                ? $this->toMinor($transaction->amount)
                : -$this->toMinor($transaction->amount));
    }

    private function toMinor(string $amount): int
    {
        if (!preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D', $amount)) {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function fromMinor(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
