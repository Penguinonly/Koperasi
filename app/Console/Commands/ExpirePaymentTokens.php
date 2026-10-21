<?php

namespace App\Console\Commands;

use App\Models\PaymentToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpirePaymentTokens extends Command
{
    protected $signature = 'tokens:expire';

    protected $description = 'Mark payment tokens expired after their synchronization grace window';

    public function handle(): int
    {
        $cutoff = now()->subHours((int) config('payment_tokens.sync_grace_hours', 24));
        $expired = 0;

        PaymentToken::query()
            ->where('status', 'ISSUED')
            ->where('expires_at', '<=', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use (&$expired) {
                foreach ($tokens as $token) {
                    $updated = PaymentToken::query()
                        ->whereKey($token->id)
                        ->where('status', 'ISSUED')
                        ->update(['status' => 'EXPIRED']);

                    if (!$updated) {
                        continue;
                    }

                    DB::table('token_sync_log')->insert([
                        'token_uuid' => $token->token_uuid,
                        'submitted_by_device' => null,
                        'submitted_at' => now(),
                        'result' => 'EXPIRED',
                        'meta' => json_encode(['reason' => 'sync_grace_elapsed'], JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                    ]);
                    $expired++;
                }
            });

        $this->info("Expired {$expired} payment token(s).");

        return self::SUCCESS;
    }
}
