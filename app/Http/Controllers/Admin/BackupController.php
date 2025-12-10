<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BackupJob;
use App\Models\Admin\BackupSetting;
use App\Services\BackupService;
use App\Services\BackupStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class BackupController extends Controller
{
    protected $backupService;
    protected $storageService;

    public function __construct(BackupService $backupService, BackupStorageService $storageService)
    {
        $this->backupService = $backupService;
        $this->storageService = $storageService;
    }

    public function index()
    {
        $jobs = BackupJob::with('staff')->orderBy('created_at', 'desc')->paginate(10);
        $settings = BackupSetting::first();
        
        // Calculate stats
        $totalBackups = BackupJob::count();
        $lastBackup = BackupJob::where('status', 'success')->latest()->first();

        return view('admin.backups.dashboard', compact('jobs', 'settings', 'totalBackups', 'lastBackup'));
    }

    public function store(Request $request)
    {
        
        $request->validate([
            'backup_type' => 'required|in:full,schema,data',
            'storage_location' => 'required|in:local,network',
        ]);

        try {
            $this->backupService->runPgDump(
                $request->backup_type,
                $request->storage_location,
                Auth::user()->staff_id // Assuming Auth::id() returns staff_id or we map it
            );

            return redirect()->route('admin.backups.index', ['backup_success' => '1']);
        } catch (Exception $e) {
            return redirect()->route('admin.backups.index')->with('error', 'Échec de la sauvegarde : ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        $job = BackupJob::findOrFail($id);
        
        if ($job->status !== 'success') {
            return redirect()->back()->with('error', 'Impossible de télécharger une sauvegarde échouée ou en cours.');
        }

        $path = $this->storageService->getPath($job->file_name, $job->storage_location);

        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Fichier introuvable.');
        }

        return response()->download($path);
    }

    public function destroy($id)
    {
        $job = BackupJob::findOrFail($id);

        try {
            $this->storageService->delete($job->file_name, $job->storage_location);
            $job->delete();
            return redirect()->route('admin.backups.index')->with('success', 'Sauvegarde supprimée avec succès.');
        } catch (Exception $e) {
            return redirect()->route('admin.backups.index')->with('error', 'Échec de la suppression : ' . $e->getMessage());
        }
    }
}
