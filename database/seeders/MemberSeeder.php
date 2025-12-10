<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member\Member;
use App\Models\Finance\Subscription;
use App\Models\Finance\Payment;
use App\Models\Finance\Plan;
use App\Models\Activity\Activity;
use App\Models\Member\AccessBadge;
use Faker\Factory as Faker;
use Carbon\Carbon;

class MemberSeeder extends Seeder
{
    /**
     * Seed 1000 members with subscriptions and payments for the last 3 years
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        $plans = Plan::all();
        if ($plans->isEmpty()) {
            $this->command->error('No plans found.');
            return;
        }

        $adminId = \App\Models\Staff\Staff::where('username', 'admin')->value('staff_id') ?? 1;
        $staffIds = \App\Models\Staff\Staff::pluck('staff_id')->toArray();
        if (empty($staffIds)) $staffIds = [$adminId];

        $totalMembers = 1000;
        $batchSize = 100; // Insert in batches for performance
        
        $this->command->info("Creating {$totalMembers} members with history. This may take a moment...");

        for ($i = 0; $i < $totalMembers; $i++) {
            // Create Member
            $gender = $faker->randomElement(['male', 'female']);
            $firstName = $faker->firstName($gender);
            $lastName = $faker->lastName;
            
            $member = Member::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $faker->unique()->safeEmail,
                'phone_number' => $faker->phoneNumber,
                'date_of_birth' => $faker->dateTimeBetween('-70 years', '-5 years'),
                'address' => $faker->address,
                'created_by' => $adminId,
                'photo_path' => null,
                'emergency_contact_name' => $faker->name,
                'emergency_contact_phone' => $faker->phoneNumber,
                'notes' => rand(0, 100) < 10 ? $faker->sentence : null, // 10% have notes
            ]);

            // Create Access Badge
            $badgeUid = mb_strtoupper(mb_substr($lastName, 0, 3) . mb_substr($firstName, 0, 1) . '-' . str_pad($member->member_id, 4, '0', STR_PAD_LEFT));
            AccessBadge::create([
                'member_id' => $member->member_id,
                'badge_uid' => $badgeUid,
                'status' => 'active',
                'issued_at' => now(),
            ]);

            // Create Subscriptions History (Last 3 years up to now)
            // Some members are new, some are old.
            // Randomly decide when they joined: 0-36 months ago
            $monthsAgoJoined = rand(0, 36);
            $joinDate = Carbon::now()->subMonths($monthsAgoJoined);
            
            $currentDate = $joinDate->copy();
            $now = Carbon::now();

            while ($currentDate->lt($now)) {
                // Pick a random plan and activity
                $plan = $plans->random(); 
                $activityId = \App\Models\Activity\Activity::inRandomOrder()->value('activity_id');
                
                // Get Price
                $price = \Illuminate\Support\Facades\DB::table('pool_schema.activity_plan_prices')
                    ->where('plan_id', $plan->plan_id)
                    ->where('activity_id', $activityId)
                    ->value('price') ?? 0;

                // Calculate end date based on plan duration
                $duration = $plan->duration_months ?? 1;
                // If plan duration is null (single entry), treat as 1 day or skip for now to simplify
                if (!$duration) $duration = 1;

                $startDate = $currentDate->copy();
                $endDate = $startDate->copy()->addMonths($duration);

                // Determine status
                $status = 'expired';
                if ($endDate->gt($now)) {
                    $status = 'active';
                }

                // Create Subscription
                $subscription = Subscription::create([
                    'member_id' => $member->member_id,
                    'plan_id' => $plan->plan_id,
                    'activity_id' => $activityId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status,
                    'visits_per_week' => $plan->visits_per_week,
                    'created_by' => $adminId,
                    'created_at' => $startDate, // Historic creation time
                ]);

                // Create Payment for this subscription
                // Sometimes paid on start date, sometimes a bit late/early
                Payment::create([
                    'subscription_id' => $subscription->subscription_id,
                    'amount' => $price,
                    'payment_date' => $startDate->copy()->addMinutes(rand(0, 600)), // Same day usually
                    'payment_method' => $faker->randomElement(['cash', 'card', 'check']),
                    'received_by_staff_id' => $faker->randomElement($staffIds),
                    'notes' => 'Paiement abonnement ' . $plan->plan_name,
                    'created_at' => $startDate,
                ]);

                // Move current date to end of this subscription (renewal)
                // Add some gap days potentially (0-30 days gap between subs)
                $gap = rand(0, 10) < 2 ? rand(1, 30) : 0; // 20% chance of a gap
                $currentDate = $endDate->copy()->addDays($gap);
                
                // If the gap pushes us past NOW, stop.
                if ($currentDate->gt($now) && $status === 'expired') {
                    break;
                }
                
                // If member "churned" (stopped coming), break loop. 
                // 10% chance to stop renewing if gap happened
                if ($gap > 0 && rand(0, 100) < 10) {
                    break;
                }
            }

            if (($i + 1) % 100 === 0) {
                $this->command->info("Processed {$i} members...");
            }
        }
    }
}
