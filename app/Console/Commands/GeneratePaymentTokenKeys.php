<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use ParagonIE\Sodium\Compat;

class GeneratePaymentTokenKeys extends Command
{
    protected $signature = 'tokens:generate-keys {--force : Replace an existing token signing keypair}';

    protected $description = 'Generate an Ed25519 keypair and store it in the local .env file';

    public function handle(): int
    {
        $envPath = base_path('.env');

        if (!is_file($envPath)) {
            $this->error('No .env file was found. Create it before generating token keys.');

            return self::FAILURE;
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            $this->error('The .env file could not be read.');

            return self::FAILURE;
        }

        if (!$this->option('force') && preg_match('/^PAYMENT_TOKEN_PRIVATE_KEY="?([^"\r\n]+)"?$/m', $contents, $matches) && trim($matches[1]) !== '') {
            $this->error('A payment token key is already configured. Use --force only when intentionally rotating it.');

            return self::FAILURE;
        }

        $keypair = Compat::crypto_sign_keypair();
        $keyId = 'server-'.now()->format('Ymd').'-'.Str::lower(Str::random(6));
        $publicKey = base64_encode(Compat::crypto_sign_publickey($keypair));
        $publicKeys = $this->readPublicKeyring($contents);
        $currentKeyId = $this->readEnvValue($contents, 'PAYMENT_TOKEN_KEY_ID');
        $currentPublicKey = $this->readEnvValue($contents, 'PAYMENT_TOKEN_PUBLIC_KEY');

        if ($currentKeyId !== '' && $currentPublicKey !== '') {
            $publicKeys[$currentKeyId] = $currentPublicKey;
        }

        $publicKeys[$keyId] = $publicKey;
        $values = [
            'PAYMENT_TOKEN_PRIVATE_KEY' => base64_encode(Compat::crypto_sign_secretkey($keypair)),
            'PAYMENT_TOKEN_PUBLIC_KEY' => $publicKey,
            'PAYMENT_TOKEN_KEY_ID' => $keyId,
            'PAYMENT_TOKEN_PUBLIC_KEYS' => implode(',', array_map(
                fn (string $id, string $key) => $id.':'.$key,
                array_keys($publicKeys),
                array_values($publicKeys),
            )),
        ];

        foreach ($values as $name => $value) {
            $line = $name.'="'.$value.'"';
            $pattern = '/^'.preg_quote($name, '/').'=.*$/m';

            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, $line, $contents, 1);
            } else {
                $contents = rtrim($contents)."\n".$line."\n";
            }
        }

        if (file_put_contents($envPath, $contents, LOCK_EX) === false) {
            $this->error('The .env file could not be updated.');

            return self::FAILURE;
        }

        $this->callSilent('config:clear');
        $this->info('Ed25519 keypair saved to .env. The private key was not printed.');
        $this->line('Active key ID: '.$values['PAYMENT_TOKEN_KEY_ID']);
        $this->line('Public keyring updated with '.count($publicKeys).' key(s).');
        $this->warn('Back up the private key securely before rotating it.');

        return self::SUCCESS;
    }

    private function readPublicKeyring(string $contents): array
    {
        $encoded = $this->readEnvValue($contents, 'PAYMENT_TOKEN_PUBLIC_KEYS');
        $keys = [];

        foreach (explode(',', $encoded) as $entry) {
            if (!str_contains($entry, ':')) {
                continue;
            }

            [$keyId, $key] = explode(':', $entry, 2);
            if ($keyId !== '' && $key !== '') {
                $keys[$keyId] = $key;
            }
        }

        return $keys;
    }

    private function readEnvValue(string $contents, string $name): string
    {
        if (!preg_match('/^'.preg_quote($name, '/').'=(.*)$/m', $contents, $matches)) {
            return '';
        }

        return trim(trim($matches[1]), "\"'");
    }
}
