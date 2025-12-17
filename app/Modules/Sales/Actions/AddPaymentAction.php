<?php

namespace App\Modules\Sales\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddPaymentAction
{
    public function execute(int $subscriptionId, float $amount, string $paymentMethod, ?string $notes, ?int $receivedByStaffId): array
    {
        return DB::transaction(function () use ($subscriptionId, $amount, $paymentMethod, $notes, $receivedByStaffId) {
            // 1. Fetch Subscription
            $subscription = DB::table('pool_schema.subscriptions')->where('subscription_id', $subscriptionId)->first();
            if (!$subscription) {
                throw new \Exception("Abonnement introuvable.");
            }

            // 2. Calculate Pricing & Remaining Balance
            // Logic mirrored from controller: get activity-plan price
            // We need to join activities via plan prices? Or just look up 'pool_schema.activity_plan_prices'?
            // The controller does a complex join to find activityId, but subscription has activity_id! 
            // Controller code:
            // $activityId = ... join ... where app.plan_id = sub.plan_id ... select activities.activity_id
            // Wait, subscription table HAS 'activity_id'. 
            // $subscription->activity_id should be correct. 
            // Let's verify schema if possible, but assuming standard, we use subscription's activity_id.
            
            $price = DB::table('pool_schema.activity_plan_prices')
                ->where('plan_id', $subscription->plan_id)
                ->where('activity_id', $subscription->activity_id)
                ->value('price');
            
            if (is_null($price)) {
                throw new \Exception("Aucun tarif trouvé pour cette activité et ce plan.");
            }

            $totalPaid = DB::table('pool_schema.payments')->where('subscription_id', $subscriptionId)->sum('amount');
            $remaining = (float)$price - $totalPaid;

            // 3. Validation
            // Allow small float diff tolerance? Controller didn't.
            if ($amount > $remaining) {
                 throw ValidationException::withMessages(['amount' => "Le montant dépasse le solde restant (" . number_format($remaining, 2) . " DZD)."]);
            }

            // 4. Record Payment
            $paymentId = DB::table('pool_schema.payments')->insertGetId([
                'subscription_id' => $subscriptionId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'received_by_staff_id' => $receivedByStaffId,
                'payment_date' => now(),
            ], 'payment_id');

            // 5. Return Stats for UI update
            $newTotal = $totalPaid + $amount;
            $newRemaining = max(0, (float)$price - $newTotal);
            $progress = $price > 0 ? round(($newTotal / $price) * 100, 1) : 0;

            return [
                'success' => true,
                'payment_id' => $paymentId,
                'summary' => [
                    'planPrice' => number_format($price, 2),
                    'totalPaid' => number_format($newTotal, 2),
                    'remaining' => number_format($newRemaining, 2),
                    'progress' => $progress,
                ]
            ];
        });
    }
}
