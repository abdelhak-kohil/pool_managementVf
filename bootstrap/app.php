<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule; // ✅ Add this use statement

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
            'pgaudit' => \App\Http\Middleware\SetPgAuditUser::class,
        ]);
    })

    // ✅ Add this block for scheduling
    ->withSchedule(function (Schedule $schedule): void {
        // Run your command every day at midnight
        $schedule->command('subscriptions:expire')->dailyAt('00:00');

        // Automatic backup scheduler - checks settings and triggers backup if conditions match
        $schedule->call(function () {
            $settings = \App\Models\Admin\BackupSetting::first();
            if ($settings && $settings->automatic_enabled) {
                $now = now();
                $scheduledTime = \Carbon\Carbon::parse($settings->scheduled_time);
                
                // Check if current time matches scheduled time (within same minute)
                if ($now->format('H:i') === $scheduledTime->format('H:i')) {
                    $shouldRun = match ($settings->frequency) {
                        'daily' => true,
                        'weekly' => $now->isSunday(),
                        'monthly' => $now->day === 1,
                        default => false,
                    };
                    
                    if ($shouldRun) {
                        \App\Jobs\RunBackupJob::dispatch(
                            'full',
                            $settings->storage_preference
                        );
                        \Log::info('Automatic backup triggered at ' . $now->toDateTimeString());
                    }
                }
            }
        })->everyMinute()->name('automatic-backup-check');

        // Cleanup old backups daily at 01:00
        $schedule->job(new \App\Jobs\CleanupOldBackupsJob)->dailyAt('01:00');
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
