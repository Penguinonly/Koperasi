<?php

$publicKeys = [];
foreach (explode(',', (string) env('PAYMENT_TOKEN_PUBLIC_KEYS', '')) as $entry) {
    if (!str_contains($entry, ':')) {
        continue;
    }

    [$keyId, $encodedKey] = explode(':', $entry, 2);
    $publicKeys[$keyId] = $encodedKey;
}

return [
    'private_key' => env('PAYMENT_TOKEN_PRIVATE_KEY'),
    'public_key' => env('PAYMENT_TOKEN_PUBLIC_KEY'),
    'key_id' => env('PAYMENT_TOKEN_KEY_ID', 'server-2026-01'),
    'public_keys' => $publicKeys,
    'sync_grace_hours' => (int) env('PAYMENT_TOKEN_SYNC_GRACE_HOURS', 24),
    'daily_token_count' => (int) env('PAYMENT_TOKEN_DAILY_COUNT', 3),
];
