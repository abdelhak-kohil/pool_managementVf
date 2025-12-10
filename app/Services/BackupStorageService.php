<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\Admin\BackupSetting;
use Exception;

class BackupStorageService
{
    protected ?BackupSetting $settings;

    public function __construct()
    {
        $this->settings = BackupSetting::first();
    }

    public function store($sourcePath, $fileName, $location)
    {
        $fileContent = file_get_contents($sourcePath);
        
        if ($location === 'network' && $this->settings && $this->settings->network_path) {
            // Copy to network path directly if it's a UNC path or mounted path
            $destination = rtrim($this->settings->network_path, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            if (!copy($sourcePath, $destination)) {
                throw new Exception("Failed to copy backup to network path: {$destination}");
            }
            return filesize($sourcePath);
        }

        // Default to local storage
        Storage::disk('local')->put('backups/' . $fileName, $fileContent);
        
        return filesize($sourcePath);
    }

    public function delete($fileName, $location)
    {
        if ($location === 'network' && $this->settings && $this->settings->network_path) {
            $path = rtrim($this->settings->network_path, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($path)) {
                unlink($path);
                return true;
            }
        }

        if (Storage::disk('local')->exists('backups/' . $fileName)) {
            Storage::disk('local')->delete('backups/' . $fileName);
            return true;
        }

        return false;
    }

    public function getPath($fileName, $location)
    {
        if ($location === 'network' && $this->settings && $this->settings->network_path) {
            return rtrim($this->settings->network_path, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        }

        return Storage::disk('local')->path('backups/' . $fileName);
    }
}
