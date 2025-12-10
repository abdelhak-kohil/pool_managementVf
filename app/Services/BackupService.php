<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Admin\BackupJob;
use Carbon\Carbon;
use Exception;

class BackupService
{
    protected $storageService;

    public function __construct(BackupStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Check if there is sufficient disk space for backup
     */
    public function checkDiskSpace(string $location): array
    {
        $minSpaceBytes = (int) env('BACKUP_MIN_DISK_SPACE_MB', 500) * 1024 * 1024;
        
        if ($location === 'network') {
            $settings = \App\Models\Admin\BackupSetting::first();
            $path = $settings?->network_path;
            
            if (!$path || !is_dir($path)) {
                return ['success' => false, 'message' => 'Chemin réseau non configuré ou inaccessible'];
            }
        } else {
            $path = storage_path('app/backups');
            
            // Create directory if it doesn't exist
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        
        $freeSpace = @disk_free_space($path);
        
        if ($freeSpace === false) {
            return ['success' => false, 'message' => 'Impossible de vérifier l\'espace disque'];
        }
        
        if ($freeSpace < $minSpaceBytes) {
            $freeSpaceMB = round($freeSpace / 1024 / 1024, 2);
            $minSpaceMB = $minSpaceBytes / 1024 / 1024;
            return [
                'success' => false, 
                'message' => "Espace disque insuffisant: {$freeSpaceMB}MB disponible, {$minSpaceMB}MB requis"
            ];
        }
        
        return ['success' => true, 'message' => 'Espace disque suffisant'];
    }

    public function runPgDump(string $type, string $location, ?int $triggeredBy = null)
    {
        // Check disk space before proceeding
        $spaceCheck = $this->checkDiskSpace($location);
        if (!$spaceCheck['success']) {
            throw new Exception($spaceCheck['message']);
        }

        $fileName = $this->generateFileName($type);
        $tempPath = storage_path('app/temp/' . $fileName);
        
        // Ensure temp directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $job = BackupJob::create([
            'backup_type' => $type,
            'file_name' => $fileName,
            'storage_location' => $location,
            'status' => 'running',
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $command = $this->buildPgDumpCommand($type, $tempPath);
            
            // Use array format for Process to avoid quoting issues
            // $command is now an array
            $process = new Process($command);
            $process->setTimeout(3600); // 1 hour timeout
            
            // Pass essential environment variables including PGPASSWORD for security
            // Detect OS and set appropriate environment variables for cross-platform compatibility
            $password = config('database.connections.pgsql.password');
            $env = $this->getEnvironmentVariables($password);
            $process->setEnv($env);

            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Backup Process Failed Output: ' . $process->getOutput());
                Log::error('Backup Process Failed Error Output: ' . $process->getErrorOutput());
                throw new ProcessFailedException($process);
            }

            // Store file to destination
            $fileSize = $this->storageService->store($tempPath, $fileName, $location);

            // Update job status
            $job->update([
                'status' => 'success',
                'file_size' => $this->humanFilesize($fileSize),
                'completed_at' => now(),
            ]);

            // Cleanup temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return $job;

        } catch (Exception $e) {
            Log::error('Backup failed: ' . $e->getMessage());
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            throw $e;
        }
    }

    protected function buildPgDumpCommand($type, $outputPath)
    {
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $database = config('database.connections.pgsql.database');
        $username = config('database.connections.pgsql.username');
        $password = config('database.connections.pgsql.password');
        
        // Configurable path to pg_dump
        $pgDumpPath = config('backup.pg_dump.path', 'pg_dump');

        $flags = match ($type) {
            'full' => '-Fc', // Custom format (compressed)
            'schema' => '-s', // Schema only
            'data' => '-a', // Data only
            default => '-Fc',
        };

        // Construct Connection URI (without password for security)
        // Password will be passed via PGPASSWORD environment variable
        $connectionUri = "postgresql://{$username}@{$host}:{$port}/{$database}";

        // Return array for Process
        return [
            $pgDumpPath,
            '-d', $connectionUri,
            '-w', // No password prompt
            $flags,
            '-f', $outputPath
        ];
    }

    /**
     * Get environment variables based on the operating system
     * Ensures cross-platform compatibility between Windows and Linux
     */
    protected function getEnvironmentVariables(string $password): array
    {
        $env = [
            'PATH' => getenv('PATH'),
            'PGPASSWORD' => $password,
        ];

        if ($this->isWindows()) {
            // Windows-specific environment variables
            $env['SystemRoot'] = getenv('SystemRoot');
            $env['TEMP'] = getenv('TEMP');
            $env['TMP'] = getenv('TMP');
        } else {
            // Linux/Unix-specific environment variables
            $env['HOME'] = getenv('HOME');
            $env['TMPDIR'] = getenv('TMPDIR') ?: '/tmp';
            $env['SHELL'] = getenv('SHELL');
            $env['USER'] = getenv('USER');
        }

        // Filter out empty/false values
        return array_filter($env, fn($value) => $value !== false && $value !== null);
    }

    /**
     * Check if the current operating system is Windows
     */
    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    protected function generateFileName($type)
    {
        $appName = config('app.name', 'laravel');
        $date = Carbon::now()->format('Y-m-d_H-i-s');
        return "{$appName}_{$type}_{$date}.dump";
    }

    protected function humanFilesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
