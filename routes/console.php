<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the expiration command to run daily at midnight
Schedule::command('subscriptions:expire')->daily();

// Schedule automated backups based on settings
Schedule::call(function () {
    $settings = \App\Models\Admin\BackupSetting::first();
    
    if (!$settings || !$settings->automatic_enabled) {
        return;
    }

    // Check if today matches the configured frequency
    $shouldRun = match ($settings->frequency) {
        'daily' => true,
        'weekly' => now()->isMonday(),
        'monthly' => now()->day === 1,
        default => false,
    };

    if ($shouldRun) {
        \App\Jobs\RunBackupJob::dispatch('full', $settings->storage_preference);
    }
})->dailyAt('00:00')->name('scheduled-backup');

// Cleanup old backups based on retention settings
Schedule::job(new \App\Jobs\CleanupOldBackupsJob)->dailyAt('01:00')->name('cleanup-backups');