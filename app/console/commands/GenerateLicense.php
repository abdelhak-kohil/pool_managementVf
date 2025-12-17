<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateLicense extends Command
{
    protected $signature = 'license:generate {client} {email} {--days=365} {--module=global}';
    protected $description = 'Generate a signed license for a specific module';

    public function handle()
    {
        $client = $this->argument('client');
        $email = $this->argument('email');
        $days = (int) $this->option('days');
        $module = \Illuminate\Support\Str::lower($this->option('module'));

        $privateKeyPath = storage_path("license_keys/{$module}/private.key");
        if (!File::exists($privateKeyPath)) {
            $this->error("Private key not found for module '{$module}'! Run 'php artisan license:keys --module={$module}' first.");
            return;
        }

        $privateKey = File::get($privateKeyPath);

        // Payload
        $payload = [
            'id' => (string) Str::uuid(),
            'module' => $module, // Embed module in payload for verification
            'client' => $client,
            'email' => $email,
            'issued_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays($days)->toIso8601String(),
            'modules' => [
                // In multi-key mode, this license ONLY authorizes this specific module.
                // However, we keep the structure flexible if we want to bundle limits in here.
                $module => ['enabled' => true, 'expires_at' => now()->addDays($days)->toIso8601String()],
            ],
            'limits' => [
                'users' => 100,
            ]
        ];

        $jsonPayload = json_encode($payload);

        // Detect OpenSSL Config path - common issue on Windows
        $opensslConfig = getenv('OPENSSL_CONF') ?: 'C:/Program Files/Common Files/SSL/openssl.cnf';
        
        $pkey = openssl_pkey_get_private($privateKey);
        if (!$pkey) {
             $this->error('Invalid Private Key: ' . openssl_error_string());
             return;
        }

        // Sign
        openssl_sign($jsonPayload, $signature, $pkey, OPENSSL_ALGO_SHA512);
        
        $licenseData = [
            'payload' => base64_encode($jsonPayload),
            'signature' => base64_encode($signature)
        ];

        $finalLicenseString = base64_encode(json_encode($licenseData));

        $this->info('License Generated Successfully!');
        $this->line('');
        $this->comment($finalLicenseString);
        $this->line('');
    }
}
