<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Licensing\Facades\License;

class LicenseController extends Controller
{
    public function index()
    {
        // Now valid globally if at least one module is valid
        $status = License::isValid(); 
        // We really want per-module status now
        // Dynamically build modules list based on Service definitions
        $statuses = License::getAllStatuses(); 
        $client = License::getClientName();
        $modules = [];
        
        foreach ($statuses as $moduleName => $isValid) {
            if ($moduleName === 'global') continue; // Skip global in this list
            $modules[$moduleName] = [
                'enabled' => License::hasModule($moduleName),
                'valid' => $isValid,
                'expiry_date' => License::getExpiryDate($moduleName)?->format('d/m/Y'),
                'days_remaining' => License::daysRemaining($moduleName),
            ];
        }
        
        return view('admin.license.index', compact('status', 'client', 'modules', 'statuses'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ]);

        if (License::activate($request->license_key)) {
            return redirect()->route('admin.license.index')->with('success', 'Licence module activée avec succès.');
        }

        return back()->with('error', 'Clé de licence invalide ou signature incorrecte.');
    }

    public function toggleStatus(Request $request)
    {
        $module = $request->input('module');
        if (!$module) {
             return back()->with('error', 'Module non spécifié.');
        }

        if (License::isValid($module)) {
            // Suspend
            License::suspend($module);
            return back()->with('success', "Licence module '{$module}' désactivée.");
        } else {
            // Try to reactivate latest
            if (License::activateLatest($module)) {
                return back()->with('success', "Licence module '{$module}' réactivée.");
            }
            return back()->with('error', "Aucune licence valide trouvée pour le module '{$module}'.");
        }
    }
}
