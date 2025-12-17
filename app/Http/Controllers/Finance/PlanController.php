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

    public function store(Request $request, \App\Modules\Catalog\Actions\CreatePlanAction $action)
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'plan_type' => 'required|in:monthly_weekly,per_visit',
            'visits_per_week' => 'nullable|integer|min:1|max:7',
            'duration_months' => 'nullable|integer|min:1|max:24',
            'is_active' => 'boolean'
        ]);

        try {
            // Apply business logic for merging nulls (optional if front-end handles it, but good safety)
            // But DTO constructor handles direct input.
            // Let's modify Request first or let DTO logic handle it?
            // Existing logic modified request. Let's keep it here or move to DTO.
            // DTO takes raw input. Let's merge into request before DTO creation so DTO gets clean data?
            // Actually, DTO standardizes keys.
            // Creating DTO:
            $dto = \App\Modules\Catalog\DTOs\PlanData::fromRequest($request);
            // However, existing logic forces nulls for 'per_visit'.
            // DTO just takes input. We should set them to null in DTO if type is per_visit.
            // Or better, logic inside Action? Or logic here?
            // Cleaner: DTOFactory/Request logic.
            // I'll adjust DTO creation to handle this specific rule or just modify request.
            // Modifying request is simplest for migration.
             if ($request->plan_type === 'per_visit') {
                $request->merge([
                    'visits_per_week' => null,
                    'duration_months' => null,
                ]);
                // Re-create DTO with modified request
                $dto = \App\Modules\Catalog\DTOs\PlanData::fromRequest($request);
            }

            $action->execute($dto);

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

    public function update(Request $request, Plan $plan, \App\Modules\Catalog\Actions\UpdatePlanAction $action)
    {
        $request->validate([
            'plan_name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'plan_type' => 'required|in:monthly_weekly,per_visit',
            'visits_per_week' => 'nullable|integer|min:1|max:7',
            'duration_months' => 'nullable|integer|min:1|max:24',
            'is_active' => 'boolean'
        ]);

        try {
            if ($request->plan_type === 'per_visit') {
                $request->merge([
                    'visits_per_week' => null,
                    'duration_months' => null,
                ]);
            }
            
            $dto = \App\Modules\Catalog\DTOs\PlanData::fromRequest($request);
            $action->execute($plan, $dto);

            return redirect()->route('plans.index')->with('success', '✅ Plan mis à jour avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur mise à jour plan : ' . $e->getMessage());
            return back()->with('error', '❌ Une erreur est survenue lors de la mise à jour du plan.');
        }
    }

    public function destroy(Plan $plan)
    {
        try {
            // Ideally use DeletePlanAction if complex checks needed.
            $plan->delete();
            return redirect()->route('plans.index')->with('success', '🗑️ Plan supprimé avec succès.');
        } catch (\Throwable $e) {
            Log::error('Erreur suppression plan : ' . $e->getMessage());
            return back()->with('error', '❌ Impossible de supprimer ce plan.');
        }
    }
}
