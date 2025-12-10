<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanController extends Controller
{
    public function index()
    {
        $plans = DB::table('pool_schema.plans')
            ->orderBy('plan_id', 'desc')
            ->get();

        return view('plans.index', compact('plans'));
    }

    public function create()
    {
        $types = ['monthly_weekly', 'per_visit'];
        return view('plans.create', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'plan_type' => 'required|in:monthly_weekly,per_visit',
            'visits_per_week' => 'nullable|integer|min:1|max:7',
            'duration_months' => 'nullable|integer|min:1|max:24',
            'is_active' => 'boolean'
        ]);

        // Apply business logic
        if ($request->plan_type === 'per_visit') {
            $request->merge([
                'visits_per_week' => null,
                'duration_months' => null,
            ]);
        }

        try {
            Plan::create($request->only([
                'plan_name', 'description', 'plan_type',
                'visits_per_week', 'duration_months', 'is_active'
            ]));

            return redirect()->route('plans.index')->with('success', '✅ Plan créé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur création plan : ' . $e->getMessage());
            return back()->with('error', '❌ Une erreur est survenue lors de la création du plan.');
        }
    }

    public function edit(Plan $plan)
    {
        $types = ['monthly_weekly', 'per_visit'];
        return view('plans.edit', compact('plan', 'types'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'plan_type' => 'required|in:monthly_weekly,per_visit',
            'visits_per_week' => 'nullable|integer|min:1|max:7',
            'duration_months' => 'nullable|integer|min:1|max:24',
            'is_active' => 'boolean'
        ]);

        if ($request->plan_type === 'per_visit') {
            $request->merge([
                'visits_per_week' => null,
                'duration_months' => null,
            ]);
        }

        try {
            $plan->update($request->only([
                'plan_name', 'description', 'plan_type',
                'visits_per_week', 'duration_months', 'is_active'
            ]));

            return redirect()->route('plans.index')->with('success', '✅ Plan mis à jour avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour plan : ' . $e->getMessage());
            return back()->with('error', '❌ Une erreur est survenue lors de la mise à jour du plan.');
        }
    }

    public function destroy(Plan $plan)
    {
        try {
            $plan->delete();
            return redirect()->route('plans.index')->with('success', '🗑️ Plan supprimé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression plan : ' . $e->getMessage());
            return back()->with('error', '❌ Impossible de supprimer ce plan.');
        }
    }
}
