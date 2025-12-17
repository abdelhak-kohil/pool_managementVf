<?php

namespace App\Modules\Catalog\DTOs;

use Illuminate\Http\Request;

class PlanData
{
    public function __construct(
        public string $plan_name,
        public ?string $description,
        public string $plan_type,
        public ?int $visits_per_week,
        public ?int $duration_months,
        public bool $is_active
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            plan_name: $request->input('plan_name'),
            description: $request->input('description'),
            plan_type: $request->input('plan_type'),
            visits_per_week: $request->input('visits_per_week'),
            duration_months: $request->input('duration_months'),
            is_active: $request->boolean('is_active', true)
        );
    }
}
