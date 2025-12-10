<?php

namespace App\Jobs;

use App\Services\BackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class RunBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $location;
    protected $triggeredBy;

    public function __construct($type = 'full', $location = 'local', $triggeredBy = null)
    {
        $this->type = $type;
        $this->location = $location;
        $this->triggeredBy = $triggeredBy;
    }

    public function handle(BackupService $backupService)
    {
        try {
            $backupService->runPgDump($this->type, $this->location, $this->triggeredBy);
        } catch (Exception $e) {
            // Log error or notify admin
            // The service already handles updating the job status in DB
            \Log::error("Backup job failed: " . $e->getMessage());
        }
    }
}
