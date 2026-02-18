<?php

namespace App\Services\Pricing;

use App\Models\Finance\Plan;
use App\Models\Member\PartnerGroup;
use App\Models\Activity\Activity;
use App\Models\Finance\PricingContract;
use Illuminate\Support\Carbon;

class PricingCalculator
{
    /**
     * Calculate the final price for a subscription based on partner contracts.
     *
     * @param PartnerGroup $group
     * @param Activity $activity
     * @param Plan $plan
     * @param int $attendeeCount Number of attendees (for per_head billing)
     * @param Carbon|null $date Date for historical pricing
     * @return array Contains 'final_price', 'original_price', 'applied_contract'
     */
    public function calculate(PartnerGroup $group, Activity $activity, Plan $plan, int $attendeeCount = 1, ?Carbon $date = null): array
    {
        // 1. Get Base Price
        $pivot = $plan->activities()->where('pool_schema.activities.activity_id', $activity->activity_id)->first();
        $basePrice = $pivot ? (float) $pivot->pivot->price : 0.00;

        // 2. Find Applicable Contracts (priority order)
        $contracts = $group->contracts()
            ->active($date)
            ->get();

        $matchedContract = null;

        // Sort by specificity: Activity Match = 2, Plan Match = 1
        $sortedContracts = $contracts->sortByDesc(function ($contract) use ($activity, $plan) {
            $score = 0;
            if ($contract->activity_id === $activity->activity_id) $score += 2;
            if ($contract->plan_id === $plan->plan_id) $score += 1;
            return $score;
        });

        // Find first matching contract
        foreach ($sortedContracts as $contract) {
            if ($contract->activity_id && $contract->activity_id !== $activity->activity_id) continue;
            if ($contract->plan_id && $contract->plan_id !== $plan->plan_id) continue;

            $matchedContract = $contract;
            break;
        }

        // 3. Calculate Final Price
        $finalPrice = $basePrice;
        $discountAmount = 0;
        $description = 'Prix standard';

        if ($matchedContract) {
            $result = $matchedContract->calculatePrice($basePrice, $attendeeCount);
            $finalPrice = $result['final_price'];
            $discountAmount = $result['discount_amount'];
            $description = $result['description'];
        }

        return [
            'original_price' => $basePrice,
            'final_price' => number_format($finalPrice, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'description' => $description,
            'applied_contract' => $matchedContract,
            'attendee_count' => $attendeeCount,
        ];
    }

    /**
     * Calculate price for a single session/attendance
     */
    public function calculateSessionPrice(PartnerGroup $group, ?Activity $activity, int $attendeeCount, ?Carbon $date = null): array
    {
        $targetDate = $date ?: now();
        
        if (!$activity) {
            return [
                'final_price' => 0,
                'description' => 'Activité inconnue',
                'contract' => null,
            ];
        }

        // 1. Find Active Subscription to determine Plan
        $subscription = $group->subscriptions()
            ->where('status', 'active')
            ->where('start_date', '<=', $targetDate)
            ->where('end_date', '>=', $targetDate)
            ->first();

        // Allow calculation without subscription if we have a contract that doesn't depend on plan
        // But traditionally base price comes from Plan...
        // For now, assume subscription is needed to get the base "Activity Plan Price"
        // Update: For fixed packages, we might not reference a specific plan price, but rather the contract rate.
        
        // If no subscription, check if we have a contract that overrides plan requirement?
        // For simplicity, let's keep requiring subscription or at least plan context.
        
        if (!$subscription) {
            return [
                'final_price' => 0,
                'description' => 'Pas d\'abonnement actif',
                'contract' => null,
            ];
        }

        // 2. Delegate to main calculate method
        return $this->calculate($group, $activity, $subscription->plan, $attendeeCount, $targetDate);
    }

    /**
     * Calculate the total price for a fixed package contract
     * 
     * @param PricingContract $contract
     * @param float $baseRate The rate per person/session defined in contract or standard
     * @return float
     */
    public function calculatePackagePrice(PricingContract $contract, float $baseRate): float
    {
        if ($contract->contract_type !== 'fixed_package') {
            return 0.00;
        }

        $sessions = $contract->total_sessions ?? 0;
        $attendees = $contract->attendees_per_session ?? 0;
        
        // Price = Sessions * Attendees * Rate
        // Logic: Rate is "Cost per person per session"
        return round($sessions * $attendees * $baseRate, 2);
    }
}

