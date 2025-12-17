<?php

namespace App\Modules\Licensing\Services;

use App\Modules\Licensing\Models\License;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    protected array $activeLicenses = []; // module => ['payload' => ..., 'model' => License]
    
    public function __construct()
    {
        $this->loadActiveLicenses();
    }

    protected function loadActiveLicenses()
    {
        // Load all active licenses
        $licenses = License::where('status', 'active')->get();

        foreach ($licenses as $license) {
            // Determine module from license or payload (fallback)
            // But verify signature FIRST with the module key derived from DB column 'module'
            $module = $license->module ?? 'global';
            
            if ($this->validateAndParse($license->license_key, $module)) {
                $this->activeLicenses[$module] = [
                    'model' => $license,
                    'payload' => $this->parsePayload($license->license_key)
                ];
            }
        }
    }
    
    protected function parsePayload(string $licenseKey): ?array
    {
        $decoded = json_decode(base64_decode($licenseKey), true);
        if (!$decoded || !isset($decoded['payload'])) {
            Log::error("License Parse Error: Failed to decode base64 or missing payload.");
            return null;
        }
        return json_decode(base64_decode($decoded['payload']), true);
    }

    public function validateAndParse(string $licenseKey, string $module = 'global'): bool
    {
        try {
            // Load Public Key for THIS module
            $keyPath = storage_path("license_keys/{$module}/public.key");
            
            if (!File::exists($keyPath)) {
                Log::error("License validation failed: Public key not found at {$keyPath}");
                return false;
            }
            
            $publicKey = File::get($keyPath);

            $decoded = json_decode(base64_decode($licenseKey), true);
            if (!$decoded || !isset($decoded['payload']) || !isset($decoded['signature'])) {
                Log::error("License validation failed: Malformed license data.");
                return false;
            }

            $payloadJson = base64_decode($decoded['payload']);
            $signature = base64_decode($decoded['signature']);

            // Verify Signature
            $valid = openssl_verify($payloadJson, $signature, $publicKey, OPENSSL_ALGO_SHA512);

            if ($valid === 1) {
                $payload = json_decode($payloadJson, true);
                
                // Check Expiration
                if (isset($payload['expires_at']) && now()->gt($payload['expires_at'])) {
                    Log::error("License validation failed: Expired.");
                    return false;
                }
                
                // Verify Module Match
                if (isset($payload['module']) && $payload['module'] !== $module) {
                    Log::error("License validation failed: Module mismatch. License is for '{$payload['module']}', expected '{$module}'.");
                    return false;
                }

                return true;
            } else {
                Log::error("License validation failed: Signature verification return code: {$valid}. OpenSSL Error: " . openssl_error_string());
            }
        } catch (\Exception $e) {
            Log::error('License Verification Exception: ' . $e->getMessage());
        }

        return false;
    }

    public function activate(string $licenseKey): bool
    {
        Log::info("Attempting license activation...");
        
        $tempPayload = $this->parsePayload($licenseKey);
        
        if (!$tempPayload) {
            Log::error("Activation failed: Could not parse payload structure.");
            return false;
        }

        if (!isset($tempPayload['module'])) {
            Log::error("Activation failed: Payload missing 'module' field. Legacy license?");
            return false; 
        }
        
        $module = $tempPayload['module'];
        Log::info("Activation detected module: {$module}");
        
        if ($this->validateAndParse($licenseKey, $module)) {
            // ... (existing success logic)
            // Deactivate existing for this module
            License::where('module', $module)->where('status', 'active')->update(['status' => 'suspended']);

            License::create([
                'module' => $module,
                'license_key' => $licenseKey,
                'client_name' => $tempPayload['client'] ?? 'Unknown',
                'email' => $tempPayload['email'] ?? null,
                'status' => 'active',
                'activated_at' => now(),
                'last_check_at' => now(),
                'metadata' => $tempPayload,
            ]);

            // Reload
            $this->loadActiveLicenses();
            Log::info("Activation successful for module: {$module}");
            return true;
        }
        
        Log::error("Activation failed: Validation returned false downstream.");
        return false;
    }

    public function hasModule(string $module): bool
    {
        // Check if we have an active valid license for this module
        if (!isset($this->activeLicenses[$module])) {
             // Fallback: Check 'global' license if it grants this module
             if (isset($this->activeLicenses['global'])) {
                 $globalPayload = $this->activeLicenses['global']['payload'];
                 $modules = $globalPayload['modules'] ?? [];
                 if (isset($modules[$module]) && ($modules[$module]['enabled'] ?? false)) {
                     return true;
                 }
             }
             return false;
        }
        
        // We have a direct license for this module, it is valid (checked at load)
        // Double check enabled flag inside (though presence confirms it usually)
        $payload = $this->activeLicenses[$module]['payload'];
        $modules = $payload['modules'] ?? [];
        
        // If the license grants THIS module specifically
        if (isset($modules[$module]) && ($modules[$module]['enabled'] ?? false)) {
             return true;
        }
        
        return false;
    }

    public function getClientName(string $module = 'global'): string
    {
        // Try specific module first, then global, then unknown
        if (isset($this->activeLicenses[$module])) {
            return $this->activeLicenses[$module]['payload']['client'] ?? 'Unknown';
        }
        if (isset($this->activeLicenses['global'])) {
            return $this->activeLicenses['global']['payload']['client'] ?? 'Unknown';
        }
        // If checking random module, return first available client name
        if (!empty($this->activeLicenses)) {
            return reset($this->activeLicenses)['payload']['client'] ?? 'Unknown';
        }

        return 'Unlicensed';
    }

    public function isValid(string $module = null): bool
    {
        if ($module) {
            return isset($this->activeLicenses[$module]);
        }
        return !empty($this->activeLicenses);
    }

    // Helper for Admin UI - List all statuses
    public function getAllStatuses(): array
    {
        $statuses = [];
        $knownModules = ['global', 'finance', 'shop', 'crm', 'operations', 'hr', 'backups'];
        
        foreach ($knownModules as $mod) {
            $statuses[$mod] = $this->isValid($mod);
        }
        return $statuses;
    }

    public function getExpiryDate(string $module = 'global')
    {
        if (!isset($this->activeLicenses[$module])) return null;
        $payload = $this->activeLicenses[$module]['payload'];
        if (!isset($payload['expires_at'])) return null;
        return \Carbon\Carbon::parse($payload['expires_at']);
    }

    public function daysRemaining(string $module = 'global'): ?int
    {
        $expiry = $this->getExpiryDate($module);
        if (!$expiry) return null; 
        
        return now()->diffInDays($expiry, false);
    }

    public function suspend(string $module): void
    {
        License::where('module', $module)->where('status', 'active')->update(['status' => 'suspended']);
        unset($this->activeLicenses[$module]);
    }

    public function activateLatest(string $module): bool
    {
        $latest = License::where('module', $module)->latest()->first();
        
        if ($latest && $this->validateAndParse($latest->license_key, $module)) {
             License::where('module', $module)->where('status', 'active')->update(['status' => 'suspended']);
             $latest->update(['status' => 'active']);
             $this->loadActiveLicenses();
             return true;
        }
        
        return false;
    }
}
