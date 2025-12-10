<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles & Staff (Essential first)
        $this->call([
            PoolRoleSeeder::class,
            StaffSeeder::class,
        ]);

        // 2. Foundation Data
        $this->call([
            WeekdaySeeder::class,
            ActivitySeeder::class,
            PlanSeeder::class,
            ActivityPlanPriceSeeder::class, // Links Plans <-> Activities
            FacilitySeeder::class,
            ProductSeeder::class,
            PartnerGroupSeeder::class,
        ]);

        // 3. Operational Data (Staff schedules, etc)
        $this->call([
            CoachSeeder::class,
            TimeSlotSeeder::class, // Weekly Planning
        ]);

        // 4. Usage & History Data (Heavy data)
        $this->call([
            MemberSeeder::class, // Includes Subscriptions & Payments history
            FreeBadgesSeeder::class,
            PoolMaintenanceSeeder::class,
            PoolWaterTestSeeder::class,
            PoolIncidentSeeder::class,
            PoolInterventionSeeder::class,
            PoolChemicalUsageSeeder::class,
            PoolTaskSeeder::class,
            ExpenseSeeder::class, // Added expenses
        ]);
    }
}
