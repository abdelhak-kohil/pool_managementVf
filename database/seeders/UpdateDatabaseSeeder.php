<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
        ]);
        
        $this->command->info('Database seeded successfully with comprehensive dataset!');
    }
}
