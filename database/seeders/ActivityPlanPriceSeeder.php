<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\Plan;
use App\Models\Activity\Activity;

class ActivityPlanPriceSeeder extends Seeder
{
    /**
     * Seed activity plan prices (Tarifications)
     */
    public function run(): void
    {
        $plans = Plan::all();
        $activities = Activity::all();

        if ($plans->isEmpty() || $activities->isEmpty()) {
            $this->command->error('Plans or Activities not found. Please seed them first.');
            return;
        }

        // Define which activities are included in standard memberships
        // Usually, standard memberships grant access to "Natation Libre" (Free Swim)
        // Specific classes (Aquagym, Lessons) might have their own specific pricing or be included in "Unlimited"

        $freeSwim = Activity::where('name', 'Natation Libre')->first();
        
        $premiumActivities = Activity::whereIn('name', [
            'Aquagym', 'Aquabike', 'Aqua Fitness', 'Aqua Zen'
        ])->get();

        foreach ($plans as $plan) {
            // All plans include Free Swim access (except maybe specific single activity ones, but let's generalize)
            if ($freeSwim) {
                // If it's a specific plan like "Prepaid 10 entries", the price is global, 
                // but we might need to link it to activities it allows.
                // For the pivot table `activity_plan_prices`, if it represents "Price of this activity FOR this plan"
                // OR if it represents "Access to this activity is included in this plan".
                // Looking at the migration: table `activity_plan_prices` has `price`. 
                // If it's 0, it might mean included. If it has a value, it might be an add-on cost?
                // OR it defines that this Plan covers this Activity.
                
                // Let's assume this pivot table defines WHAT activities are accessible with a given plan.
                // The price column in the pivot might override the base price or be irrelevant if the plan has a global price.
                // However, usually "Member" plans cover Free Swim.
                
                // Let's link Free Swim to all monthly/quarterly/annual plans
                if (in_array($plan->plan_type, ['monthly', 'quarterly', 'annual', 'student', 'senior', 'family'])) {
                    DB::table('pool_schema.activity_plan_prices')->insert([
                        'activity_id' => $freeSwim->activity_id,
                        'plan_id'     => $plan->plan_id,
                        'price'       => 0.00 // Included in subscription
                    ]);
                }
            }

            // Unlimited plans might include Aqua classes
            if (str_contains($plan->plan_name, 'Illimité') || str_contains($plan->plan_name, 'Unlimited')) {
                foreach ($premiumActivities as $activity) {
                    DB::table('pool_schema.activity_plan_prices')->insert([
                        'activity_id' => $activity->activity_id,
                        'plan_id'     => $plan->plan_id,
                        'price'       => 0.00 // Included in unlimited
                    ]);
                }
            }
        }
        
        // Single Entry logic
        $singlePlan = Plan::where('plan_type', 'single')->first();
        if ($singlePlan) {
            // Single entry valid for almost anything, but maybe diff prices?
            // The Plan itself has a price (1000). 
            // Let's link it to all activities.
            foreach ($activities as $activity) {
                 DB::table('pool_schema.activity_plan_prices')->insert([
                    'activity_id' => $activity->activity_id,
                    'plan_id'     => $singlePlan->plan_id,
                    'price'       => 0.00 // Base plan price covers it
                ]);
            }
        }

        $this->command->info('Seeded Activity Plan Prices (Tarifications).');
    }
}
