<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolDailyTask;
use App\Models\Pool\PoolWeeklyTask;
use App\Models\Pool\PoolMonthlyTask;
use App\Models\Pool\Facility;
use App\Models\Staff\Staff;

class PoolTaskSeeder extends Seeder
{
    public function run()
    {
        $pools = Facility::where('active', true)->get();
        $technician = Staff::whereHas('role', function($q) {
            $q->where('role_name', 'pool_technician');
        })->first() ?? Staff::first();

        if ($pools->isEmpty()) {
            return;
        }

        // Ensure templates exist by calling TaskTemplateSeeder if needed
        if (\App\Models\Pool\TaskTemplate::count() === 0) {
            $this->call(TaskTemplateSeeder::class);
        }

        // Get Active Templates
        $dailyTemplate = \App\Models\Pool\TaskTemplate::where('type', 'daily')->where('is_active', true)->first();
        $weeklyTemplate = \App\Models\Pool\TaskTemplate::where('type', 'weekly')->where('is_active', true)->first();
        $monthlyTemplate = \App\Models\Pool\TaskTemplate::where('type', 'monthly')->where('is_active', true)->first();

        foreach ($pools as $pool) {
            // Seed Daily Tasks (Today and Yesterday)
            $dailyData = [
                'technician_id' => $technician->staff_id,
                'pump_status' => 'ok',
                'pressure_reading' => 1.2,
                'skimmer_cleaned' => true,
                'vacuum_done' => false,
                'drains_checked' => true,
                'lighting_checked' => true,
                'debris_removed' => true,
                'drain_covers_inspected' => true,
                'clarity_test_passed' => true,
                'anomalies_comment' => 'RAS',
            ];
            
            if ($dailyTemplate) {
                $dailyData['template_id'] = $dailyTemplate->id;
                $dailyData['custom_data'] = $dailyData; // Store same data in custom_data for consistency
            }

            PoolDailyTask::firstOrCreate(
                [
                    'pool_id' => $pool->facility_id,
                    'task_date' => today(),
                ],
                $dailyData
            );

            $yesterdayData = [
                'technician_id' => $technician->staff_id,
                'pump_status' => 'ok',
                'pressure_reading' => 1.1,
                'skimmer_cleaned' => true,
                'vacuum_done' => true,
                'drains_checked' => true,
                'lighting_checked' => true,
                'debris_removed' => true,
                'drain_covers_inspected' => true,
                'clarity_test_passed' => true,
                'anomalies_comment' => 'Nettoyage approfondi effectué.',
            ];

            if ($dailyTemplate) {
                $yesterdayData['template_id'] = $dailyTemplate->id;
                $yesterdayData['custom_data'] = $yesterdayData;
            }

            PoolDailyTask::firstOrCreate(
                [
                    'pool_id' => $pool->facility_id,
                    'task_date' => today()->subDay(),
                ],
                $yesterdayData
            );

            // Seed Weekly Tasks (Current Week)
            $weeklyData = [
                'technician_id' => $technician->staff_id,
                'backwash_done' => true,
                'filter_cleaned' => true,
                'brushing_done' => false,
                'heater_checked' => true,
                'chemical_doser_checked' => true,
                'fittings_retightened' => false,
                'heater_tested' => true,
                'general_inspection_comment' => 'Vérification hebdomadaire en cours.',
            ];

            if ($weeklyTemplate) {
                $weeklyData['template_id'] = $weeklyTemplate->id;
                $weeklyData['custom_data'] = $weeklyData;
            }

            PoolWeeklyTask::firstOrCreate(
                [
                    'pool_id' => $pool->facility_id,
                    'week_number' => now()->weekOfYear,
                    'year' => now()->year,
                ],
                $weeklyData
            );

            // Seed Monthly Tasks (Current Month)
            $monthlyData = [
                'technician_id' => $technician->staff_id,
                'water_replacement_partial' => true,
                'full_system_inspection' => true,
                'chemical_dosing_calibration' => false,
                'notes' => 'Calibration à prévoir semaine prochaine.',
            ];

            if ($monthlyTemplate) {
                $monthlyData['template_id'] = $monthlyTemplate->id;
                $monthlyData['custom_data'] = $monthlyData;
            }

            PoolMonthlyTask::firstOrCreate(
                [
                    'facility_id' => $pool->facility_id,
                    'completed_at' => now()->startOfMonth(), // Use start of month to avoid duplicates on re-seed
                ],
                $monthlyData
            );
        }
    }
}
