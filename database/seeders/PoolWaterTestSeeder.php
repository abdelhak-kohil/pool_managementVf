<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolWaterTest;
use App\Models\Pool\Facility;
use App\Models\Staff\Staff;

class PoolWaterTestSeeder extends Seeder
{
    public function run()
    {
        $pools = Facility::where('active', true)->get();
        // Get a technician (staff with role 'technician' or just the first staff member)
        $technician = Staff::first(); 

        if ($pools->isEmpty() || !$technician) {
            return;
        }

        foreach ($pools as $pool) {
            // Create data for the last 365 days
            for ($i = 0; $i < 365; $i++) {
                $date = now()->subDays($i)->setHour(rand(8, 11))->setMinute(rand(0, 59));
                
                PoolWaterTest::create([
                    'pool_id' => $pool->facility_id,
                    'technician_id' => $technician->staff_id,
                    'test_date' => $date, // Correct column name based on model
                    'ph' => rand(70, 78) / 10, // 7.0 - 7.8
                    'chlorine_free' => rand(5, 30) / 10, // 0.5 - 3.0
                    'chlorine_total' => rand(5, 35) / 10, // 0.5 - 3.5
                    'temperature' => rand(260, 300) / 10, // 26.0 - 30.0
                    'alkalinity' => rand(80, 120),
                    'hardness' => rand(200, 400),
                    'salinity' => rand(3000, 4000),
                    'turbidity' => rand(0, 10) / 10, // 0.0 - 1.0
                    'orp' => rand(650, 800),
                    'comments' => $i % 30 === 0 ? 'Routine monthly check' : null,
                ]);
            }
        }
    }
}
