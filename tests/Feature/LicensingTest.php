<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Auth\User;
use App\Modules\Licensing\Facades\License;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LicensingTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $keys = []; // module => ['private' => ..., 'public' => ...]

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->withoutMiddleware(\App\Http\Middleware\SetPgAuditUser::class);
        
        $this->admin = User::whereHas('role', function($q) {
            $q->where('role_name', 'admin');
        })->first();

        if (!$this->admin) {
             $role = \App\Models\Admin\Role::firstOrCreate(['role_name' => 'admin']);
             $this->admin = User::create([
                'name' => 'Admin Test',
                'email' => 'admin_test@pool.com',
                'password' => bcrypt('password'),
                'role_id' => $role->id,
            ]);
        }

        // Generate KEYS for modules
        $this->generateKeysForModule('shop');
        $this->generateKeysForModule('finance');
        
        // Reload Service to pick up new keys (if it caches constructs)
        // License facade might need clearing if it's a singleton resolving once
        \App\Modules\Licensing\Facades\License::clearResolvedInstance(\App\Modules\Licensing\Services\LicenseService::class);
        // app()->forget(...) does not exist on Application in recent versions directly as 'forget', use 'forgetInstance'
        if (method_exists(app(), 'forgetInstance')) {
            app()->forgetInstance(\App\Modules\Licensing\Services\LicenseService::class);
        } else {
            // Fallback for older laravel or if accessed as array
            unset(app()[\App\Modules\Licensing\Services\LicenseService::class]);
        }
    }

    protected function generateKeysForModule($module)
    {
        $config = [
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        
        // Robust Windows OpenSSL Config Detection
        $possiblePaths = [
            getenv('OPENSSL_CONF'),
            'C:/Program Files/Common Files/SSL/openssl.cnf',
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/laragon/bin/php/php-8.1.10-Win32-vs16-x64/extras/ssl/openssl.cnf',
            'C:/Program Files/OpenSSL-Win64/bin/openssl.cfg',
            'C:/Windows/System32/openssl.cnf',
             // Fallback to PHP default location relative to executable?
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                $config['config'] = $path;
                break;
            }
        }

        $res = openssl_pkey_new($config);
        
        if (!$res) {
            throw new \Exception("OpenSSL Key Gen Failed: " . openssl_error_string() . " | Config: " . json_encode($config));
        }

        openssl_pkey_export($res, $privKey, null, $config);
        $pubKey = openssl_pkey_get_details($res)['key'];

        $path = storage_path("license_keys/{$module}");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        File::put($path . '/private.key', $privKey);
        File::put($path . '/public.key', $pubKey);

        $this->keys[$module] = ['private' => $privKey, 'public' => $pubKey];
    }

    protected function generateTestLicense($module, $modulesPayload = [])
    {
        $payload = [
            'id' => (string) Str::uuid(),
            'module' => $module,
            'client' => 'Test PHPUnit',
            'email' => 'test@phpunit.com',
            'issued_at' => now()->toIso8601String(),
            'expires_at' => now()->addDays(30)->toIso8601String(),
            'modules' => $modulesPayload,
            'limits' => ['users' => 10]
        ];

        $jsonPayload = json_encode($payload);
        
        // Sign with THAT module's private key
        $pkey = openssl_pkey_get_private($this->keys[$module]['private']);
        openssl_sign($jsonPayload, $signature, $pkey, OPENSSL_ALGO_SHA512);

        $licenseData = [
            'payload' => base64_encode($jsonPayload),
            'signature' => base64_encode($signature)
        ];

        return base64_encode(json_encode($licenseData));
    }

    public function test_it_can_activate_shop_license_independently()
    {
        $licenseKey = $this->generateTestLicense('shop', [
            'shop' => ['enabled' => true]
        ]);

        $success = License::activate($licenseKey);

        $this->assertTrue($success, 'Activation of Shop failed');
        $this->assertTrue(License::hasModule('shop'));
        $this->assertFalse(License::hasModule('finance')); // Finance not active
    }

    public function test_it_can_activate_multiple_modules()
    {
        // 1. Activate Shop
        $shopKey = $this->generateTestLicense('shop', ['shop' => ['enabled' => true]]);
        License::activate($shopKey);

        // 2. Activate Finance
        $financeKey = $this->generateTestLicense('finance', ['finance' => ['enabled' => true]]);
        License::activate($financeKey);

        // 3. Verify BOTH are active
        $this->assertTrue(License::hasModule('shop'));
        $this->assertTrue(License::hasModule('finance'));
    }

    public function test_it_rejects_key_signed_by_wrong_module()
    {
        // Create payload claiming to be 'finance'
        $payload = [
            'id' => (string) Str::uuid(),
            'module' => 'finance', // Claims to be finance
            'modules' => ['finance' => ['enabled' => true]]
        ];
        $jsonPayload = json_encode($payload);

        // BUT sign it with 'shop' private key
        $pkey = openssl_pkey_get_private($this->keys['shop']['private']);
        openssl_sign($jsonPayload, $signature, $pkey, OPENSSL_ALGO_SHA512);

        $licenseData = [
            'payload' => base64_encode($jsonPayload),
            'signature' => base64_encode($signature)
        ];
        $fakeLicenseKey = base64_encode(json_encode($licenseData));

        // Attempt activation
        $success = License::activate($fakeLicenseKey);

        $this->assertFalse($success, 'Should verify signature against FINANCE key, so SHOP signature should fail');
        $this->assertFalse(License::hasModule('finance'));
    }
}
