<?php

namespace App\Jobs;

use App\Models\Admin\BackupJob;
use App\Models\Admin\BackupSetting;
use App\Services\BackupStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(BackupStorageService $storageService)
    {
        $settings = BackupSetting::first();
        
        if (!$settings || !$settings->retention_days) {
            return;
        }

        $cutoffDate = now()->subDays($settings->retention_days);
        
        $oldBackups = BackupJob::where('created_at', '<', $cutoffDate)
            ->where('status', 'success')
            ->get();

        foreach ($oldBackups as $backup) {
            try {
                $deleted = $storageService->delete($backup->file_name, $backup->storage_location);
                
                if ($deleted) {
                    $backup->delete(); // Or mark as deleted/archived
                    \Log::info("Deleted old backup: {$backup->file_name}");
                }
            } catch (\Exception $e) {
                \Log::error("Failed to delete old backup {$backup->file_name}: " . $e->getMessage());
            }
        }
    }
}
