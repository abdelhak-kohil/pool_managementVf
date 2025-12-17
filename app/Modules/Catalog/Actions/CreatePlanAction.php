<?php

namespace App\Modules\Catalog\Actions;

use App\Models\Finance\Plan;
use App\Modules\Catalog\DTOs\PlanData;
use Illuminate\Support\Facades\DB;

class CreatePlanAction
{
    public function execute(PlanData $data): Plan
    {
        return DB::transaction(function () use ($data) {
            return Plan::create([
                'plan_name'       => $data->plan_name,
                'description'     => $data->description,
                'plan_type'       => $data->plan_type,
                'visits_per_week' => $data->visits_per_week,
                'duration_months' => $data->duration_months,
                'is_active'       => $data->is_active,
            ]);
        });
    }
}
