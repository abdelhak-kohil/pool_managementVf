<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolMaintenance;
use App\Models\Pool\PoolEquipment;
use App\Models\Staff\Staff;

class PoolInterventionSeeder extends Seeder
{
    public function run()
    {
        $equipment = PoolEquipment::first();
        $staff = Staff::first();

        if (!$equipment || !$staff) return;

        PoolMaintenance::create([
            'equipment_id' => $equipment->equipment_id,
            'technician_id' => $staff->staff_id,
            'task_type' => 'repair', // type -> task_type
            'description' => 'Replaced seal on pump.',
            'status' => 'completed',
            'scheduled_date' => now()->subMonth(),
            'completed_date' => now()->subMonth(),
            // 'cost' => 150.00, // cost column not in schema
        ]);

        PoolMaintenance::create([
            'equipment_id' => $equipment->equipment_id,
            'technician_id' => $staff->staff_id,
            'task_type' => 'preventive', // type -> task_type
            'description' => 'Annual service.',
            'status' => 'scheduled',
            'scheduled_date' => now()->addWeek(),
        ]);
    }
}
