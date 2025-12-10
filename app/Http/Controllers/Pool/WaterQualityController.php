<?php

namespace App\Http\Controllers\Pool;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pool\StoreWaterTestRequest;
use App\Models\Pool\PoolWaterTest;
use App\Models\Pool\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WaterQualityController extends Controller
{
    public function index(Request $request)
    {
        $query = PoolWaterTest::with(['pool', 'technician'])
            ->orderBy('test_date', 'desc');

        if ($request->has('pool_id')) {
            $query->where('pool_id', $request->pool_id);
        }

        $tests = $query->paginate(20);
        $pools = Facility::where('active', true)->get();

        return view('pool.water-tests.index', compact('tests', 'pools'));
    }

    public function create()
    {
        $pools = Facility::where('active', true)->get();
        return view('pool.water-tests.create', compact('pools'));
    }

    public function store(StoreWaterTestRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $data['technician_id'] = $user->staff_id;

        $test = PoolWaterTest::create($data);

        // Comprehensive Alert Logic
        $alerts = [];
        
        // pH (7.2 - 7.8)
        if ($test->ph && ($test->ph < 7.2 || $test->ph > 7.8)) {
            $alerts[] = "pH hors normes ({$test->ph}) - Idéal: 7.2-7.8";
        }

        // Free Chlorine (1.0 - 3.0 ppm)
        if ($test->chlorine_free && ($test->chlorine_free < 1.0 || $test->chlorine_free > 3.0)) {
            $alerts[] = "Chlore libre hors normes ({$test->chlorine_free} ppm) - Idéal: 1.0-3.0";
        }

        // Combined Chlorine (Total - Free) should be < 0.5
        if ($test->chlorine_total && $test->chlorine_free) {
            $combined = $test->chlorine_total - $test->chlorine_free;
            if ($combined > 0.5) {
                $alerts[] = "Chlore combiné élevé ({$combined} ppm) - Max: 0.5";
            }
        }

        // Bromine (3.0 - 5.0 ppm)
        if ($test->bromine && ($test->bromine < 3.0 || $test->bromine > 5.0)) {
            $alerts[] = "Brome hors normes ({$test->bromine} ppm) - Idéal: 3.0-5.0";
        }

        // Alkalinity (80 - 120 ppm)
        if ($test->alkalinity && ($test->alkalinity < 80 || $test->alkalinity > 120)) {
            $alerts[] = "Alcalinité hors normes ({$test->alkalinity} ppm) - Idéal: 80-120";
        }

        // Hardness (200 - 400 ppm)
        if ($test->hardness && ($test->hardness < 200 || $test->hardness > 400)) {
            $alerts[] = "Dureté hors normes ({$test->hardness} ppm) - Idéal: 200-400";
        }

        // ORP (min 650 mV)
        if ($test->orp && $test->orp < 650) {
            $alerts[] = "ORP trop faible ({$test->orp} mV) - Min: 650";
        }

        // Turbidity (max 1 NTU)
        if ($test->turbidity && $test->turbidity > 1.0) {
            $alerts[] = "Turbidité élevée ({$test->turbidity} NTU) - Max: 1.0";
        }

        if (!empty($alerts)) {
            return redirect()->route('pool.water-tests.index')
                ->with('success', 'Test enregistré.')
                ->with('warning', implode(' | ', $alerts));
        }

        return redirect()->route('pool.water-tests.index')
            ->with('success', 'Test enregistré avec succès. Paramètres nominaux.');
    }

    public function show(PoolWaterTest $waterTest)
    {
        return view('pool.water-tests.show', compact('waterTest'));
    }
    
    // API endpoint for charts
    public function history(Request $request, $poolId)
    {
        $days = $request->get('days', 30);
        
        if ($poolId === 'all' || !$poolId) {
            // Fetch data for all pools
            $data = PoolWaterTest::with('pool:facility_id,name')
                ->where('test_date', '>=', now()->subDays($days))
                ->orderBy('test_date', 'asc')
                ->get(['pool_id', 'test_date', 'ph', 'chlorine_free', 'temperature']);
                
            // Group by pool
            $grouped = $data->groupBy('pool_id')->map(function ($tests) {
                $poolName = $tests->first()->pool->name ?? 'Unknown Pool';
                return [
                    'name' => $poolName,
                    'data' => $tests->map(function ($test) {
                        return [
                            'test_date' => $test->test_date,
                            'ph' => $test->ph,
                            'chlorine_free' => $test->chlorine_free,
                        ];
                    })->values()
                ];
            });

            return response()->json(['type' => 'multiple', 'datasets' => $grouped]);
        }

        // Single pool data
        $data = PoolWaterTest::where('pool_id', $poolId)
            ->where('test_date', '>=', now()->subDays($days))
            ->orderBy('test_date', 'asc')
            ->get(['test_date', 'ph', 'chlorine_free', 'temperature', 'orp']);
            
        return response()->json(['type' => 'single', 'data' => $data]);
    }

    public function exportPdf(Request $request)
    {
        $query = PoolWaterTest::with(['pool', 'technician'])
            ->orderBy('test_date', 'desc');

        if ($request->has('pool_id')) {
            $query->where('pool_id', $request->pool_id);
        }

        $tests = $query->get();

        $pdf = \PDF::loadView('pool.water-tests.pdf', compact('tests'));
        return $pdf->download('rapport-qualite-eau-' . date('Y-m-d') . '.pdf');
    }
}
