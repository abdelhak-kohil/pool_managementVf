<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateLicenseKeys extends Command
{
    protected $signature = 'license:keys {--module=global : The module name for this key pair}';
    protected $description = 'Generate RSA Public/Private keys for licensing a specific module';

    public function handle()
    {
        $module = \Illuminate\Support\Str::lower($this->option('module'));
        $this->info("Generating keys for module: {$module}");

        // Detect OpenSSL Config path - common issue on Windows
        $opensslConfig = getenv('OPENSSL_CONF') ?: 'C:/Program Files/Common Files/SSL/openssl.cnf';
        $config = []; // Initialize config array
        
        if (!file_exists($opensslConfig)) {
             $possiblePaths = [
                 'C:/xampp/php/extras/ssl/openssl.cnf',
                 'C:/laragon/bin/php/php-8.1.10-Win32-vs16-x64/extras/ssl/openssl.cnf', 
                 'C:/Program Files/OpenSSL-Win64/bin/openssl.cfg',
             ];
             
             foreach ($possiblePaths as $path) {
                 if (file_exists($path)) {
                     $config['config'] = $path;
                     break;
                 }
             }
        } else {
             $config['config'] = $opensslConfig;
        }

        // Create the private and public key
        $res = openssl_pkey_new($config);
        
        if (!$res) {
            $this->error('OpenSSL Error: ' . openssl_error_string());
            return;
        }

        // Extract the private key from $res to $privKey
        $exportSuccess = openssl_pkey_export($res, $privKey, null, $config);
        
        if (!$exportSuccess) {
            $this->error('Export Error: ' . openssl_error_string());
            return;
        }

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        // Save to storage/license_keys/{module}/
        $path = storage_path("license_keys/{$module}");
        if (!File::exists($path)) {
            File::makeDirectory($path);
        }

        File::put($path . '/private.key', $privKey);
        File::put($path . '/public.key', $pubKey);

        $this->info('RSA Keys generated successfully in storage/license_keys');
        $this->warn('KEEP PRIVATE KEY SAFE and SECURE! Deploy PUBLIC KEY with the app.');
    }
}
