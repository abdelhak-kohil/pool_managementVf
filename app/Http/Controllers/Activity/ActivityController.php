<?php

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Models\Activity\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index()
    {
        $activities = Activity::orderBy('name')->paginate(10);
        return view('activities.index', compact('activities'));
    }

    public function create()
    {
        return view('activities.create');
    }

    public function store(Request $request)
    {
        try {
        
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:activities,name',
            'description' => 'nullable|string',
            'access_type' => 'nullable|string|max:50',
            'color_code' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);
        
        Activity::create($data);
        return redirect()->route('activities.index')->with('success', 'Activité ajoutée avec succès.');
        }catch (\Throwable $e) {
            Log::error('Erreur ajouter activity : ' . $e->getMessage());
            return back()->with('error', 'Impossible d\'ajouter cette activité.');
        }
    
    }

    public function edit(Activity $activity)
    {
        return view('activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:activities,name,' . $activity->activity_id . ',activity_id',
            'description' => 'nullable|string',
            'access_type' => 'nullable|string|max:50',
            'color_code' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        $activity->update($data);
        return redirect()->route('activities.index')->with('success', 'Activité mise à jour avec succès.');
    }



    public function getPlans($activityId)
{
    $plans = DB::table('pool_schema.activity_plan_prices as app')
        ->join('pool_schema.plans as p', 'p.plan_id', '=', 'app.plan_id')
        ->select('p.plan_id', 'p.plan_name', 'app.price')
        ->where('app.activity_id', $activityId)
        ->where('p.is_active', true)
        ->get();

    return response()->json($plans);
}


    public function destroy(Activity $activity)
    {
        $activity->delete();
        return redirect()->route('activities.index')->with('success', 'Activité supprimée.');
    }
}
