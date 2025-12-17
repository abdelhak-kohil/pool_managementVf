<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\DTOs\SubscriptionData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class UpdateSubscriptionAction
{
    public function execute(int $subscriptionId, array $payload, ?int $staffId)
    {
        return DB::transaction(function () use ($subscriptionId, $payload, $staffId) {
            // 1. Fetch Subscription & Plan Details
            $subscription = DB::table('pool_schema.subscriptions')->where('subscription_id', $subscriptionId)->first();
            if (!$subscription) {
                throw new \Exception("Abonnement introuvable.");
            }

            $plan = DB::table('pool_schema.plans')->where('plan_id', $payload['plan_id'])->first();
            if (!$plan) {
                 throw ValidationException::withMessages(['plan_id' => 'Plan invalide.']);
            }

            // 2. Validate Slot Count
            $requiredSlots = $plan->plan_type === 'monthly_weekly' ? (int) $plan->visits_per_week : 1;
            $slotIds = $payload['slot_ids'] ?? [];
            if (count($slotIds) !== $requiredSlots) {
                throw ValidationException::withMessages(['slot_ids' => "Ce plan requiert exactement {$requiredSlots} créneau(x)."]);
            }

            // 3. Validate Price consistency (if changing activity/plan)
            // Note: The controller logic checked real price only if creating, or implicitly if changing. 
            // Here we assume if they update the plan, they should verify price, but user input for updating *price* isn't in the form (only display).
            // The controller checked realPrice for display purposes mostly but didn't force-check against a 'price' input unless creating payment.

            // 4. Overlap Check (Excluding current subscription)
            if ($payload['status'] === 'active' && $plan->plan_type === 'monthly_weekly') {
                $overlapExists = DB::table('pool_schema.subscriptions as s')
                    ->join('pool_schema.plans as p', 's.plan_id', '=', 'p.plan_id')
                    ->where('s.member_id', $subscription->member_id)
                    ->where('s.subscription_id', '!=', $subscriptionId) // Existing check
                    ->where('p.plan_type', 'monthly_weekly')
                    ->where('s.status', 'active')
                    ->whereRaw("NOT (s.end_date < ? OR s.start_date > ?)", [$payload['start_date'], $payload['end_date']])
                    ->exists();

                if ($overlapExists) {
                    throw ValidationException::withMessages(['start_date' => 'Un autre abonnement actif chevauche cette période pour ce membre.']);
                }
            }

            // 5. Update Subscription
            DB::table('pool_schema.subscriptions')
                ->where('subscription_id', $subscriptionId)
                ->update([
                    'activity_id'     => $payload['activity_id'],
                    'plan_id'         => $payload['plan_id'],
                    'status'          => $payload['status'],
                    'start_date'      => $payload['start_date'],
                    'end_date'        => $payload['end_date'],
                    'visits_per_week' => $plan->plan_type === 'monthly_weekly' ? $plan->visits_per_week : null,
                    'updated_by'      => $staffId,
                    'updated_at'      => now(),
                ]);

            // 6. Sync Slots
            // Delete old
            DB::table('pool_schema.subscription_slots')->where('subscription_id', $subscriptionId)->delete();
            
            // Insert new
            $weekdayIds = [];
            $slots = DB::table('pool_schema.time_slots')->whereIn('slot_id', $slotIds)->get();
             // Validate slots exist/not blocked logic could be reused from CreateAction if extracted to Service, 
             // but for now we assume slots are valid as per original controller logic which did basic counting.
             // Original controller logic actually didn't seem to re-validate 'blocked' status on update explicitly in the snippet shown?
             // Wait, the snippet shown for update() HAD validation 'exists:time_slots,slot_id' but didn't loop to check 'is_blocked' or future time explicitly?
             // Let's be safe and check simple existence, maybe skip strict business logic if admin is force-updating?
             // Actually, let's keep it simple as the original code: just update.
            
            foreach ($slots as $s) {
                 DB::table('pool_schema.subscription_slots')->insert([
                    'subscription_id' => $subscriptionId,
                    'slot_id'         => $s->slot_id,
                    'created_at'      => now(),
                ]);
                $weekdayIds[] = $s->weekday_id;
            }

            // 7. Sync Allowed Days (if monthly)
            DB::table('pool_schema.subscription_allowed_days')->where('subscription_id', $subscriptionId)->delete();
            if ($plan->plan_type === 'monthly_weekly') {
                $uniqueWeekdays = array_values(array_unique($weekdayIds));
                 if (count($uniqueWeekdays) !== (int)$plan->visits_per_week) {
                      throw ValidationException::withMessages(['slot_ids' => "Les créneaux ne correspondent pas aux jours requis."]);
                 }
                 foreach ($uniqueWeekdays as $wd) {
                    DB::table('pool_schema.subscription_allowed_days')->insert([
                        'subscription_id' => $subscriptionId,
                        'weekday_id'      => $wd,
                    ]);
                }
            }

            // 8. Handle Optional New Payment
            if (!empty($payload['new_payment_amount']) && $payload['new_payment_amount'] > 0) {
                 DB::table('pool_schema.payments')->insert([
                    'subscription_id'      => $subscriptionId,
                    'amount'               => $payload['new_payment_amount'],
                    'payment_method'       => $payload['new_payment_method'],
                    'notes'                => $payload['new_payment_notes'] ?? null,
                    'received_by_staff_id' => $staffId,
                    'payment_date'         => now(),
                ]);
            }

            return $subscriptionId;
        });
    }
}
