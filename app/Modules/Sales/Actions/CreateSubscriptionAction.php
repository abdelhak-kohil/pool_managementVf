<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\SubscriptionData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

class CreateSubscriptionAction
{
    protected $pricingCalculator;

    public function __construct(\App\Services\Pricing\PricingCalculator $pricingCalculator)
    {
        $this->pricingCalculator = $pricingCalculator;
    }

    public function execute(SubscriptionData $data): int
    {
        try {
            return DB::transaction(function () use ($data) {
            // 1. Fetch Plan & Activity details
            $plan = DB::table('pool_schema.plans')->where('plan_id', $data->plan_id)->first();
            if (!$plan) {
                throw ValidationException::withMessages(['plan_id' => 'Plan invalide.']);
            }

            // 2. Business Rules Validation
            $this->validatePlanRules($plan, $data);

            // 3. Slot Validation
            $requiredSlotsCount = $plan->plan_type === 'monthly_weekly' ? (int) $plan->visits_per_week : 1;
            if (count($data->slot_ids) !== $requiredSlotsCount) {
                throw ValidationException::withMessages(['slot_ids' => "Ce plan requiert exactement {$requiredSlotsCount} créaneau(x)."]);
            }

            $slots = $this->validateAndFetchSlots($data->slot_ids, $plan->plan_type);

            // 4. Price Validation
            $this->validatePrice($data);

            // 5. Overlap Check for Monthly Plans
            if ($plan->plan_type === 'monthly_weekly') {
                $this->checkOverlap($data);
            }

            // 6. Insert Subscription
            $subscriptionId = DB::table('pool_schema.subscriptions')->insertGetId([
                'member_id'       => $data->member_id,
                'partner_group_id'=> $data->partner_group_id,
                'plan_id'         => $data->plan_id,
                'activity_id'     => $data->activity_id,
                'start_date'      => $data->start_date,
                'end_date'        => $data->end_date,
                'status'          => $data->status,
                'visits_per_week' => $plan->plan_type === 'monthly_weekly' ? $plan->visits_per_week : null,
                'created_by'      => $data->staff_id,
                'updated_by'      => $data->staff_id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ], 'subscription_id');

            // 7. Insert Slots & Allowed Days
            $weekdayIds = [];
            foreach ($slots as $s) {
                DB::table('pool_schema.subscription_slots')->insert([
                    'subscription_id' => $subscriptionId,
                    'slot_id'         => $s->slot_id,
                    'created_at'      => now(),
                ]);
                $weekdayIds[] = $s->weekday_id;
            }

            if ($plan->plan_type === 'monthly_weekly') {
                $uniqueWeekdays = array_values(array_unique($weekdayIds));
                if (count($uniqueWeekdays) !== (int)$plan->visits_per_week) {
                     // Rollback handled by transaction
                     throw ValidationException::withMessages(['slot_ids' => "Les créneaux choisis ne correspondent pas exactement aux {$plan->visits_per_week} jour(s) requis par le plan."]);
                }

                foreach ($uniqueWeekdays as $wd) {
                    DB::table('pool_schema.subscription_allowed_days')->insert([
                        'subscription_id' => $subscriptionId,
                        'weekday_id'      => $wd,
                    ]);
                }
            }

            // 8. Create Initial Payment
            DB::table('pool_schema.payments')->insert([
                'subscription_id'      => $subscriptionId,
                'amount'               => $data->amount,
                'payment_method'       => $data->payment_method,
                'notes'                => $data->notes,
                'received_by_staff_id' => $data->staff_id,
                'payment_date'         => now(),
            ]);

            return $subscriptionId;
        });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('CreateSubscriptionAction Error: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile());
            throw $e;
        }
    }

    private function validatePlanRules($plan, SubscriptionData $data): void
    {
        if ($plan->plan_type === 'monthly_weekly') {
            if ($data->start_date->day !== 1) {
                throw ValidationException::withMessages(['start_date' => 'Les abonnements mensuels doivent commencer le 1er du mois.']);
            }
            if ($data->start_date->startOfDay()->lt(now()->startOfDay())) {
                 throw ValidationException::withMessages(['start_date' => 'La date de début ne peut pas être antérieure à aujourd’hui.']);
            }
            if (!$data->end_date->isSameDay($data->start_date->copy()->endOfMonth())) {
                 throw ValidationException::withMessages(['end_date' => 'La date de fin doit être le dernier jour du mois pour un abonnement mensuel.']);
            }
        }

        if ($plan->plan_type === 'per_visit') {
            $today = now()->startOfDay();
            if (!$data->start_date->isSameDay($today) || !$data->end_date->isSameDay($today)) {
                throw ValidationException::withMessages(['start_date' => 'Les abonnements à la séance doivent être pour aujourd’hui.']);
            }
        }
    }

    private function validateAndFetchSlots(array $slotIds, string $planType)
    {
        $slots = DB::table('pool_schema.time_slots')
            ->whereIn('slot_id', $slotIds)
            ->get();

        if ($slots->count() !== count($slotIds)) {
            throw ValidationException::withMessages(['slot_ids' => 'Un ou plusieurs créneaux sélectionnés sont introuvables.']);
        }

        $seenDays = [];
        foreach ($slots as $s) {
            if (in_array($s->day_name, $seenDays)) {
                throw ValidationException::withMessages(['slot_ids' => "Vous ne pouvez pas sélectionner plusieurs créneaux pour un même jour ({$s->day_name})."]);
            }
            $seenDays[] = $s->day_name;

            if ($s->is_blocked) {
                throw ValidationException::withMessages(['slot_ids' => "Le créneau {$s->slot_id} est bloqué."]);
            }

            $existing = DB::table('pool_schema.reservations')
                ->where('slot_id', $s->slot_id)
                ->where('status', 'confirmed')
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages(['slot_ids' => "Le créneau {$s->slot_id} est déjà réservé."]);
            }

            if ($planType === 'per_visit') {
                 $slotDateTime = now()->setTimeFrom(Carbon::parse($s->start_time));
                 if ($slotDateTime->lt(now())) {
                     throw ValidationException::withMessages(['slot_ids' => "Le créneau de {$s->start_time} est déjà passé."]);
                 }
            }
        }

        return $slots;
    }

    private function validatePrice(SubscriptionData $data): void
    {
        // 1. Partner Group Pricing Validation
        if ($data->partner_group_id) {
            $group = \App\Models\Member\PartnerGroup::find($data->partner_group_id);
            $plan = \App\Models\Finance\Plan::find($data->plan_id);
            $activity = \App\Models\Activity\Activity::find($data->activity_id);

            // Should exist due to previous checks implicitly, but safe to check
            if ($group && $plan && $activity) {
                $calc = $this->pricingCalculator->calculate($group, $activity, $plan);
                $maxPrice = (float) $calc['final_price'];
                
                // Allow a small error margin for float comparison or just exact
                // Checking if tried to pay MORE than calculated (overpayment check)
                if ($data->amount > $maxPrice) {
                     // We format to 2 decimals to be readable
                     throw ValidationException::withMessages([
                         'amount' => 'Le montant dépasse le tarif partenaire calculé (' . number_format($maxPrice, 2) . ' DZD).'
                     ]);
                }
            }
            return;
        }

        // 2. Standard Member Pricing Validation
        $activityPlan = DB::table('pool_schema.activity_plan_prices')
            ->where('plan_id', $data->plan_id)
            ->where('activity_id', $data->activity_id)
            ->first();

        if (!$activityPlan) {
            throw ValidationException::withMessages(['activity_id' => 'Aucun tarif trouvé pour la combinaison activité/plan sélectionnée.']);
        }

        if ($data->amount > (float) $activityPlan->price) {
             throw ValidationException::withMessages(['amount' => 'Le montant dépasse le prix réel (' . $activityPlan->price . ').']);
        }
    }

    private function checkOverlap(SubscriptionData $data): void
    {
        if (!$data->member_id) {
            return; // Skip overlap check for groups (for now or permanently if groups allow stacking)
        }

        $overlapExists = DB::table('pool_schema.subscriptions as s')
            ->join('pool_schema.plans as p', 's.plan_id', '=', 'p.plan_id')
            ->where('s.member_id', $data->member_id)
            ->where('p.plan_type', 'monthly_weekly')
            ->where('s.status', 'active')
            ->whereRaw("NOT (s.end_date < ? OR s.start_date > ?)", [$data->start_date, $data->end_date])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages(['member_id' => 'Le membre possède déjà un abonnement qui chevauche cette période.']);
        }
    }
}
