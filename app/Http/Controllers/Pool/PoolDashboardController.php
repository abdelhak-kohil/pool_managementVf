<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Models\Pool\PoolWaterTest;
use App\Models\Pool\PoolIncident;
use App\Models\Pool\PoolMaintenance;
use App\Models\Pool\PoolChemical;
use App\Models\Pool\Facility;
use Illuminate\Http\Request;

class PoolDashboardController extends Controller
{
    public function index()
    {
        // 1. Water Quality Status (Latest test for main pool)
        $mainPool = Facility::where('type', 'main_pool')->first();
        $latestTest = null;
        if ($mainPool) {
            $latestTest = PoolWaterTest::where('pool_id', $mainPool->facility_id)
                ->latest('test_date')
                ->first();
        }

        // 2. Pending Incidents
        $pendingIncidents = PoolIncident::whereIn('status', ['open', 'assigned', 'in_progress'])
            ->count();

        // 3. Maintenance Tasks Today
        $maintenanceToday = PoolMaintenance::whereDate('scheduled_date', today())
            ->where('status', '!=', 'completed')
            ->count();

        // 4. Low Stock Chemicals
        $lowStockChemicals = PoolChemical::whereColumn('quantity_available', '<=', 'minimum_threshold')
            ->get();

        // 5. Chart Data (Last 3 months)
        $chartData = [];
        if ($mainPool) {
            $history = PoolWaterTest::where('pool_id', $mainPool->facility_id)
                ->where('test_date', '>=', now()->subDays(90))
                ->orderBy('test_date', 'asc')
                ->get(['test_date', 'ph', 'chlorine_free', 'temperature']);

            $chartData = [
                'dates' => $history->pluck('test_date')->map(fn($d) => $d->format('Y-m-d'))->toArray(),
                'ph' => $history->pluck('ph')->toArray(),
                'chlorine' => $history->pluck('chlorine_free')->toArray(),
                'temperature' => $history->pluck('temperature')->toArray(),
            ];
        }

        return view('pool.dashboard', compact(
            'latestTest',
            'pendingIncidents',
            'maintenanceToday',
            'lowStockChemicals',
            'chartData'
        ));
    }
}
