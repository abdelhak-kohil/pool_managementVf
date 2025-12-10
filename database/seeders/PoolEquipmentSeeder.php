<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolEquipment;
use App\Models\Pool\Facility;

class PoolEquipmentSeeder extends Seeder
{
    public function run()
    {
        $pool = Facility::where('type', 'main_pool')->first();
        
        if (!$pool) {
            // Fallback if no pool exists yet
            return;
        }

        $equipment = [
            [
                'name' => 'Main Pump A',
                'type' => 'pump',
                'serial_number' => 'PUMP-2024-001',
                'location' => 'Technical Room 1',
                'install_date' => '2024-01-15',
                'status' => 'operational',
                'next_due_date' => now()->addMonths(3),
            ],
            [
                'name' => 'Sand Filter 1',
                'type' => 'filter',
                'serial_number' => 'FILT-2024-001',
                'location' => 'Technical Room 1',
                'install_date' => '2024-01-15',
                'status' => 'operational',
                'next_due_date' => now()->addMonths(1),
            ],
            [
                'name' => 'Chlorine Dosing Pump',
                'type' => 'dosing_machine',
                'serial_number' => 'DOSE-2024-001',
                'location' => 'Technical Room 1',
                'install_date' => '2024-02-01',
                'status' => 'warning',
                'notes' => 'Flow rate slightly unstable',
                'next_due_date' => now()->addDays(5),
            ],
            [
                'name' => 'Heat Pump',
                'type' => 'heater',
                'serial_number' => 'HEAT-2024-001',
                'location' => 'External Unit',
                'install_date' => '2023-11-20',
                'status' => 'operational',
                'next_due_date' => now()->addMonths(6),
            ],
        ];

        foreach ($equipment as $item) {
            PoolEquipment::create($item);
        }
    }
}
