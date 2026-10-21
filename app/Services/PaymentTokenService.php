<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\LoanTokenCounter;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ParagonIE\Sodium\Compat;
use Throwable;

class PaymentTokenService
{
    public function issueDailyTokens(User $collector, string $deviceId): array
    {
        $issuedAt = now()->startOfSecond();
        $loans = Loan::query()
            ->where('collector_id', $collector->id)
            ->where('status', 'ACTIVE')
            ->whereHas('schedules', fn ($query) => $query
                ->where('status', '!=', 'PAID')
                ->whereDate('due_date', '<=', $issuedAt->toDateString()))
            ->orderBy('id')
            ->get(['id']);
        $bundles = [];

        foreach ($loans as $candidate) {
            $bundle = DB::transaction(function () use ($candidate, $collector, $deviceId, $issuedAt) {
                $loan = Loan::query()->with('customer')->whereKey($candidate->id)->lockForUpdate()->first();
                if (!$loan || $loan->status !== 'ACTIVE' || $loan->collector_id !== $collector->id) {
                    return null;
                }

                $existing = PaymentToken::query()
                    ->where('loan_id', $loan->id)
                    ->where('issued_by', $collector->id)
                    ->whereDate('issued_at', $issuedAt->toDateString())
                    ->orderBy('sequence')
                    ->get();

                if ($existing->isNotEmpty()) {
                    return $this->tokenBundle($loan, $existing, $deviceId);
                }

                $maxAmountMinor = $this->outstandingDueMinor($loan, $issuedAt->toDateString());
                if ($maxAmountMinor < 1) {
                    return null;
                }

                $counter = LoanTokenCounter::query()->firstOrCreate(
                    ['loan_id' => $loan->id],
                    ['last_issued_sequence' => 0, 'last_confirmed_sequence' => 0],
                );
                $counter = LoanTokenCounter::query()->whereKey($counter->id)->lockForUpdate()->firstOrFail();
                $tokenCount = max(1, min(5, (int) config('payment_tokens.daily_token_count', 3)));
                $expiresAt = $issuedAt->copy()->endOfDay();
                $issuedTokens = collect();

                for ($offset = 1; $offset <= $tokenCount; $offset++) {
                    $sequence = $counter->last_issued_sequence + $offset;
                    $payload = [
                        'version' => 1,
                        'key_id' => (string) config('payment_tokens.key_id'),
                        'token_uuid' => (string) Str::uuid(),
                        'loan_id' => (int) $loan->id,
                        'sequence' => $sequence,
                        'issued_by' => (int) $collector->id,
                        'max_amount_minor' => $maxAmountMinor,
                        'issued_at' => $this->timestamp($issuedAt),
                        'expires_at' => $this->timestamp($expiresAt),
                    ];
                    $signature = $this->sign($payload);

                    $issuedTokens->push(PaymentToken::query()->create([
                        'token_uuid' => $payload['token_uuid'],
                        'loan_id' => $loan->id,
                        'sequence' => $sequence,
                        'issued_by' => $collector->id,
                        'max_amount' => $this->decimalFromMinor($maxAmountMinor),
                        'issued_at' => $issuedAt,
                        'expires_at' => $expiresAt,
                        'signature' => $signature,
                        'key_id' => $payload['key_id'],
                        'payload_version' => 1,
                        'status' => 'ISSUED',
                    ]));
                }

                $counter->update(['last_issued_sequence' => $counter->last_issued_sequence + $tokenCount]);

                return $this->tokenBundle($loan, $issuedTokens, $deviceId);
            });

            if ($bundle) {
                $bundles[] = $bundle;
            }
        }

        return [
            'server_time' => now()->toIso8601String(),
            'sync_grace_hours' => (int) config('payment_tokens.sync_grace_hours', 24),
            'active_key_id' => (string) config('payment_tokens.key_id'),
            'public_keys' => $this->publicKeyring(),
            'loans' => $bundles,
        ];
    }

    public function confirmClaim(User $actor, array $claim): array
    {
        $tokenData = $claim['token'] ?? [];
        $payload = $tokenData['payload'] ?? [];
        $tokenUuid = is_array($payload) ? ($payload['token_uuid'] ?? null) : null;
        $deviceId = (string) ($claim['device_id'] ?? '');

        if (!is_string($tokenUuid) || !Str::isUuid($tokenUuid)) {
            return ['status' => 'REJECTED', 'reason' => 'invalid_token_uuid'];
        }

        return DB::transaction(function () use ($actor, $claim, $tokenData, $payload, $tokenUuid, $deviceId) {
            if (!$this->validSignature($payload, $tokenData['signature'] ?? null)) {
                $this->logResult($tokenUuid, $deviceId, 'SIGNATURE_INVALID');

                return ['status' => 'SIGNATURE_INVALID', 'token_uuid' => $tokenUuid];
            }

            $token = PaymentToken::query()
                ->where('token_uuid', $tokenUuid)
                ->lockForUpdate()
                ->first();

            if (!$token || !$this->payloadMatchesToken($payload, $token, $tokenData['signature'] ?? '')) {
                $this->logResult($tokenUuid, $deviceId, 'REJECTED', ['reason' => 'token_not_found_or_payload_mismatch']);

                return ['status' => 'REJECTED', 'token_uuid' => $tokenUuid, 'reason' => 'token_not_found'];
            }

            $loan = Loan::query()->with('customer')->whereKey($token->loan_id)->lockForUpdate()->firstOrFail();
            if (!$this->actorMayConfirm($actor, $token, $loan)) {
                $this->logResult($tokenUuid, $deviceId, 'REJECTED', ['reason' => 'actor_not_associated_with_token']);

                return ['status' => 'REJECTED', 'token_uuid' => $tokenUuid, 'reason' => 'not_authorized'];
            }

            $amountMinor = $this->minorFromInput($claim['confirmed_amount'] ?? null);
            if ($amountMinor < 1) {
                $this->logResult($tokenUuid, $deviceId, 'REJECTED', ['reason' => 'invalid_amount']);

                return ['status' => 'REJECTED', 'token_uuid' => $tokenUuid, 'reason' => 'invalid_amount'];
            }

            $paymentMethod = $claim['payment_method'] ?? 'CASH';
            if ($paymentMethod !== 'CASH') {
                $this->logResult($tokenUuid, $deviceId, 'REJECTED', ['reason' => 'invalid_payment_method']);

                return ['status' => 'REJECTED', 'token_uuid' => $tokenUuid, 'reason' => 'invalid_payment_method'];
            }

            if ($token->status === 'CONFIRMED') {
                $existingPayment = $token->payment;
                if ($this->minorFromInput($token->confirmed_amount) === $amountMinor
                    && $existingPayment?->payment_method === $paymentMethod) {
                    $this->logResult($tokenUuid, $deviceId, 'DUPLICATE', ['payment_id' => $existingPayment->id]);

                    return ['status' => 'DUPLICATE', 'token_uuid' => $tokenUuid, 'payment_id' => $existingPayment->id];
                }

                $this->logResult($tokenUuid, $deviceId, 'SEQUENCE_CONFLICT', ['reason' => 'confirmed_claim_mismatch']);

                return ['status' => 'SEQUENCE_CONFLICT', 'token_uuid' => $tokenUuid];
            }

            $syncDeadline = $token->expires_at->copy()->addHours((int) config('payment_tokens.sync_grace_hours', 24));
            if ($token->status === 'EXPIRED' || now()->greaterThan($syncDeadline)) {
                $token->update(['status' => 'EXPIRED']);
                $this->logResult($tokenUuid, $deviceId, 'EXPIRED', ['sync_deadline' => $syncDeadline->toIso8601String()]);

                return ['status' => 'EXPIRED', 'token_uuid' => $tokenUuid];
            }

            $counter = LoanTokenCounter::query()->where('loan_id', $loan->id)->lockForUpdate()->first();
            if (!$counter || $token->sequence <= $counter->last_confirmed_sequence) {
                $this->logResult($tokenUuid, $deviceId, 'SEQUENCE_CONFLICT', [
                    'last_confirmed_sequence' => $counter?->last_confirmed_sequence,
                ]);

                return ['status' => 'SEQUENCE_CONFLICT', 'token_uuid' => $tokenUuid];
            }

            $maximumMinor = $this->minorFromInput($token->max_amount);
            $outstandingMinor = $this->outstandingDueMinor($loan, today()->toDateString());
            if ($amountMinor > $maximumMinor || $amountMinor > $outstandingMinor) {
                $this->logResult($tokenUuid, $deviceId, 'REJECTED', [
                    'reason' => $amountMinor > $maximumMinor ? 'amount_exceeds_token_limit' : 'amount_exceeds_due_balance',
                    'requested_amount_minor' => $amountMinor,
                    'max_amount_minor' => $maximumMinor,
                    'outstanding_due_minor' => $outstandingMinor,
                ]);

                return ['status' => 'REJECTED', 'token_uuid' => $tokenUuid, 'reason' => 'amount_exceeds_limit'];
            }

            $schedules = LoanSchedule::query()
                ->with('allocations')
                ->where('loan_id', $loan->id)
                ->where('status', '!=', 'PAID')
                ->whereDate('due_date', '<=', today())
                ->orderBy('due_date')
                ->orderBy('day_number')
                ->lockForUpdate()
                ->get();

            $payment = Payment::query()->create([
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'payment_token_id' => $token->id,
                'amount' => $this->decimalFromMinor($amountMinor),
                'payment_method' => $paymentMethod,
                'status' => 'COMPLETED',
                'reference_code' => 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8)),
                'paid_at' => now(),
            ]);

            $remainingMinor = $amountMinor;
            foreach ($schedules as $schedule) {
                if ($remainingMinor < 1) {
                    break;
                }

                $allocatedMinor = $schedule->allocations->sum(fn ($allocation) => $this->minorFromInput($allocation->amount));
                $dueMinor = $this->minorFromInput($schedule->amount_due);
                $scheduleRemaining = max(0, $dueMinor - $allocatedMinor);
                if ($scheduleRemaining < 1) {
                    continue;
                }

                $allocationMinor = min($remainingMinor, $scheduleRemaining);
                PaymentAllocation::query()->create([
                    'payment_id' => $payment->id,
                    'loan_schedule_id' => $schedule->id,
                    'amount' => $this->decimalFromMinor($allocationMinor),
                ]);
                $remainingMinor -= $allocationMinor;

                if ($allocationMinor === $scheduleRemaining) {
                    $schedule->update(['status' => 'PAID', 'paid_at' => now()]);
                }
            }

            if ($remainingMinor > 0) {
                throw new \LogicException('Payment allocation did not cover the confirmed amount.');
            }

            $token->update([
                'status' => 'CONFIRMED',
                'confirmed_at' => now(),
                'confirmed_amount' => $this->decimalFromMinor($amountMinor),
                'redeemed_offline_at' => $claim['redeemed_offline_at'] ?? null,
                'device_id' => $deviceId,
            ]);
            $counter->update(['last_confirmed_sequence' => $token->sequence]);
            PaymentToken::query()
                ->where('loan_id', $loan->id)
                ->where('sequence', '<', $token->sequence)
                ->where('status', 'ISSUED')
                ->update(['status' => 'EXPIRED']);

            if (!$loan->schedules()->where('status', '!=', 'PAID')->exists()) {
                $loan->update(['status' => 'COMPLETED', 'completed_at' => now()]);
            }

            $streak = $loan->customer->refreshPaymentStreak();
            $this->logResult($tokenUuid, $deviceId, 'ACCEPTED', ['payment_id' => $payment->id]);

            return [
                'status' => 'ACCEPTED',
                'token_uuid' => $tokenUuid,
                'payment_id' => $payment->id,
                'amount' => $this->decimalFromMinor($amountMinor),
                'reference_code' => $payment->reference_code,
                'streak_updated' => $streak,
                'confirmed_at' => $token->confirmed_at?->toIso8601String(),
            ];
        });
    }

    private function tokenBundle(Loan $loan, $tokens, string $deviceId): array
    {
        return [
            'loan_id' => $loan->id,
            'customer' => [
                'id' => $loan->customer->id,
                'member_code' => $loan->customer->member_code,
                'name' => $loan->customer->name,
            ],
            'device_id' => $deviceId,
            'tokens' => $tokens->map(function (PaymentToken $token) {
                $payload = $this->payloadFromToken($token);
                $signature = $this->base64UrlEncode(base64_decode($token->signature, true) ?: '');
                $envelope = json_encode(['payload' => $payload, 'signature' => $signature], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

                return [
                    'payload' => $payload,
                    'signature' => $signature,
                    'qr_data' => $this->base64UrlEncode($envelope),
                    'status' => $token->status,
                ];
            })->values()->all(),
        ];
    }

    private function payloadFromToken(PaymentToken $token): array
    {
        return [
            'version' => (int) $token->payload_version,
            'key_id' => $token->key_id,
            'token_uuid' => $token->token_uuid,
            'loan_id' => (int) $token->loan_id,
            'sequence' => (int) $token->sequence,
            'issued_by' => (int) $token->issued_by,
            'max_amount_minor' => $this->minorFromInput($token->max_amount),
            'issued_at' => $this->timestamp($token->issued_at),
            'expires_at' => $this->timestamp($token->expires_at),
        ];
    }

    private function canonicalPayload(array $payload): string
    {
        $canonical = [
            'version' => (int) ($payload['version'] ?? 0),
            'key_id' => (string) ($payload['key_id'] ?? ''),
            'token_uuid' => (string) ($payload['token_uuid'] ?? ''),
            'loan_id' => (int) ($payload['loan_id'] ?? 0),
            'sequence' => (int) ($payload['sequence'] ?? 0),
            'issued_by' => (int) ($payload['issued_by'] ?? 0),
            'max_amount_minor' => (int) ($payload['max_amount_minor'] ?? 0),
            'issued_at' => (string) ($payload['issued_at'] ?? ''),
            'expires_at' => (string) ($payload['expires_at'] ?? ''),
        ];

        return json_encode($canonical, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function sign(array $payload): string
    {
        $secretKey = $this->decodedKey('private_key');
        if (strlen($secretKey) !== 64) {
            throw new \RuntimeException('Payment token private key must be a base64-encoded Ed25519 secret key.');
        }

        return base64_encode(Compat::crypto_sign_detached($this->canonicalPayload($payload), $secretKey));
    }

    private function validSignature(array $payload, mixed $signature): bool
    {
        if (!is_string($signature) || ($payload['version'] ?? null) !== 1 || !is_string($payload['key_id'] ?? null)) {
            return false;
        }

        $binarySignature = $this->base64UrlDecode($signature);
        $keyring = config('payment_tokens.public_keys', []);
        $encodedPublicKey = $keyring[$payload['key_id']] ?? null;
        if ($encodedPublicKey === null && hash_equals((string) config('payment_tokens.key_id'), $payload['key_id'])) {
            $encodedPublicKey = config('payment_tokens.public_key');
        }
        $publicKey = is_string($encodedPublicKey) ? base64_decode($encodedPublicKey, true) : false;

        if ($binarySignature === false || strlen($binarySignature) !== 64 || $publicKey === false || strlen($publicKey) !== 32) {
            return false;
        }

        try {
            return Compat::crypto_sign_verify_detached($binarySignature, $this->canonicalPayload($payload), $publicKey);
        } catch (Throwable) {
            return false;
        }
    }

    private function payloadMatchesToken(array $payload, PaymentToken $token, string $signature): bool
    {
        try {
            $providedSignature = $this->base64UrlDecode($signature);
            $storedSignature = base64_decode($token->signature, true);

            return hash_equals(
                $this->canonicalPayload($this->payloadFromToken($token)),
                $this->canonicalPayload($payload),
            ) && $providedSignature !== false
                && $storedSignature !== false
                && hash_equals($storedSignature, $providedSignature);
        } catch (Throwable) {
            return false;
        }
    }

    private function actorMayConfirm(User $actor, PaymentToken $token, Loan $loan): bool
    {
        if ($actor->hasRole('collector')) {
            return $actor->id === $token->issued_by && $loan->collector_id === $actor->id;
        }

        return $actor->hasRole('customer') && $loan->customer->user_id === $actor->id;
    }

    private function outstandingDueMinor(Loan $loan, string $date): int
    {
        $schedules = LoanSchedule::query()
            ->with('allocations')
            ->where('loan_id', $loan->id)
            ->where('status', '!=', 'PAID')
            ->whereDate('due_date', '<=', $date)
            ->get();

        return $schedules->sum(function (LoanSchedule $schedule) {
            $allocated = $schedule->allocations->sum(fn ($allocation) => $this->minorFromInput($allocation->amount));

            return max(0, $this->minorFromInput($schedule->amount_due) - $allocated);
        });
    }

    private function minorFromInput(mixed $amount): int
    {
        if (!is_string($amount) && !is_int($amount) && !is_float($amount)) {
            return 0;
        }

        $value = trim((string) $amount);
        if (!preg_match('/^(?:0|[1-9][0-9]*)(?:\.[0-9]{1,2})?$/D', $value)) {
            return 0;
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function decimalFromMinor(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    private function timestamp(Carbon $value): string
    {
        return $value->copy()->utc()->format('Y-m-d\TH:i:s\Z');
    }

    private function decodedKey(string $name): string
    {
        $encoded = (string) config('payment_tokens.'.$name, '');
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw new \RuntimeException('Payment token '.str_replace('_', ' ', $name).' is not valid base64.');
        }

        return $decoded;
    }

    private function publicKeyring(): array
    {
        $keys = config('payment_tokens.public_keys', []);
        $activeKeyId = (string) config('payment_tokens.key_id');
        $activePublicKey = (string) config('payment_tokens.public_key');

        if ($activePublicKey !== '') {
            $keys[$activeKeyId] = $activePublicKey;
        }

        return $keys;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string|false
    {
        $base64 = strtr($value, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);

        return base64_decode($base64, true);
    }

    private function logResult(string $tokenUuid, string $deviceId, string $result, array $meta = []): void
    {
        DB::table('token_sync_log')->insert([
            'token_uuid' => $tokenUuid,
            'submitted_by_device' => $deviceId !== '' ? $deviceId : null,
            'submitted_at' => now(),
            'result' => $result,
            'meta' => $meta ? json_encode($meta, JSON_THROW_ON_ERROR) : null,
            'created_at' => now(),
        ]);
    }
}
