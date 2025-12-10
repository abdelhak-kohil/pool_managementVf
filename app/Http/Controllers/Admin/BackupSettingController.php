<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\BackupSetting;
use Illuminate\Http\Request;

class BackupSettingController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'frequency' => 'required|in:daily,weekly,monthly',
            'scheduled_time' => 'required',
            'retention_days' => 'required|integer|min:1',
            'storage_preference' => 'required|in:local,network',
            'network_path' => 'nullable|string',
        ]);

        $settings = BackupSetting::first();
        if (!$settings) {
            $settings = new BackupSetting();
        }

        $settings->automatic_enabled = $request->has('automatic_enabled');
        $settings->frequency = $request->frequency;
        $settings->scheduled_time = $request->scheduled_time;
        $settings->retention_days = $request->retention_days;
        $settings->storage_preference = $request->storage_preference;
        $settings->network_path = $request->network_path;
        
        $settings->save();

        return redirect()->route('admin.backups.index')->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Test network connection for backup storage
     */
    public function testConnection(Request $request)
    {
        $request->validate(['network_path' => 'required|string']);
        
        $path = $request->network_path;
        
        // Check if path exists and is a directory
        if (!is_dir($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Le chemin n\'existe pas ou n\'est pas accessible.'
            ]);
        }
        
        // Check if path is writable
        if (!is_writable($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Le chemin n\'est pas accessible en écriture.'
            ]);
        }
        
        // Check available disk space
        $freeSpace = @disk_free_space($path);
        if ($freeSpace !== false) {
            $freeSpaceGB = round($freeSpace / 1024 / 1024 / 1024, 2);
            return response()->json([
                'success' => true,
                'message' => "Connexion réussie! Espace disponible: {$freeSpaceGB} GB"
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie!'
        ]);
    }
}
