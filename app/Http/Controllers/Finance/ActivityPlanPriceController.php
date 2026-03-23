<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Activity\ActivityPlanPrice;
use App\Models\Activity\Activity;
use App\Models\Finance\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityPlanPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $prices = ActivityPlanPrice::with(['activity', 'plan'])
            ->orderBy('activity_id')
            ->orderBy('plan_id')
            ->get();

        return view('activity_plan_prices.index', compact('prices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();

        return view('activity_plan_prices.create', compact('activities', 'plans'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,activity_id',
            'plan_id' => 'required|exists:plans,plan_id',
            'price' => 'required|numeric|min:0',
        ]);

        DB::table('pool_schema.activity_plan_prices')->insert([
            'activity_id' => $request->activity_id,
            'plan_id' => $request->plan_id,
            'price' => $request->price,
        ]);

        return redirect()
            ->route('activity-plan-prices.index')
            ->with('success', 'Tarification ajoutée avec succès.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $price = ActivityPlanPrice::with(['activity', 'plan'])->findOrFail($id);
        $activities = Activity::where('is_active', true)->orderBy('name')->get();
        $plans = Plan::where('is_active', true)->orderBy('plan_name')->get();

        return view('activity_plan_prices.edit', compact('price', 'activities', 'plans'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,activity_id',
            'plan_id' => 'required|exists:plans,plan_id',
            'price' => 'required|numeric|min:0',
        ]);

        $price = ActivityPlanPrice::findOrFail($id);
        $price->update($request->only('activity_id', 'plan_id', 'price'));

        return redirect()
            ->route('activity-plan-prices.index')
            ->with('success', 'Tarification mise à jour avec succès.');
    }


/**
 * Return all available plans and their prices for a given activity.
 */
public function getByActivity(Request $request, $activity_id)
    {
        try {
            $plan_id = $request->get('plan');
            $price = null;

            // 🔹 1. Récupération du prix (si plan sélectionné)
            if ($plan_id) {
                $price = DB::table('pool_schema.activity_plan_prices')
                    ->where('activity_id', $activity_id)
                    ->where('plan_id', $plan_id)
                    ->value('price');

                
            }

            $plan = plan::find($plan_id);

            $plan_type = $plan->plan_type;

            // 🔹 2. Vérification de la colonne "activity_id" dans time_slots
            $columns = collect(DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'time_slots' AND table_schema = 'pool_schema'"))
                ->pluck('column_name')->toArray();

            $hasActivityId = in_array('activity_id', $columns);

            // 🔹 3. Requête dynamique sécurisée
            $sql = "
                SELECT 
                    ts.slot_id,
                    wd.day_name,
                    to_char(ts.start_time, 'HH24:MI') AS start_time,
                    to_char(ts.end_time, 'HH24:MI') AS end_time
                FROM pool_schema.time_slots ts
                JOIN pool_schema.weekdays wd ON wd.weekday_id = ts.weekday_id
            ";

            if ($hasActivityId) {
                $sql .= " LEFT JOIN pool_schema.activities act ON act.activity_id = ts.activity_id
                          WHERE (act.name = 'VIDE' OR ts.activity_id = ?)";
                $slots = DB::select($sql . " ORDER BY wd.weekday_id, ts.start_time", [$activity_id]);
            } else {
                // Si pas de colonne activity_id, afficher tous les créneaux
                $slots = DB::select($sql . " ORDER BY wd.weekday_id, ts.start_time");
            }

            return response()->json([
                'success' => true,
                'price' => $price ? floatval($price) : null,
                'slots' => $slots,
                'plan_type' => $plan_type,
            ]);
        } catch (\Throwable $e) {
            // 🔥 Capture propre de l'erreur
            return response()->json([
                'success' => false,
                'message' => 'Erreur interne : ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $price = ActivityPlanPrice::findOrFail($id);
        $price->delete();

    

        return redirect()->route('activity-plan-prices.index')
                         ->with('success', 'Tarification supprimée avec succès.');
    
    }
}
