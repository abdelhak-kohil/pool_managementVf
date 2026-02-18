<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Activity\Activity;
use App\Models\Finance\Plan;
use App\Models\Member\PartnerGroup;
use App\Services\Pricing\PricingCalculator;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    protected $calculator;

    public function __construct(PricingCalculator $calculator)
    {
        $this->calculator = $calculator;
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'partner_group_id' => 'required|exists:pool_schema.partner_groups,group_id',
            'activity_id'      => 'required|exists:pool_schema.activities,activity_id',
            'plan_id'          => 'required|exists:pool_schema.plans,plan_id',
        ]);

        $group = PartnerGroup::findOrFail($request->partner_group_id);
        $activity = Activity::findOrFail($request->activity_id);
        $plan = Plan::findOrFail($request->plan_id);

        $result = $this->calculator->calculate($group, $activity, $plan);

        return response()->json($result);
    }
}
