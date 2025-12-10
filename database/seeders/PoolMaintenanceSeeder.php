<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\Facility;
use Illuminate\Support\Facades\DB;

class PoolMaintenanceSeeder extends Seeder
{
    public function run()
    {
        // Ensure we have at least one pool facility
        $pool = Facility::firstOrCreate(
            ['name' => 'Grand Bassin Olympique'],
            [
                'type' => 'main_pool',
                'capacity' => 50,
                'status' => 'operational',
                'volume_liters' => 2500000,
                'min_temperature' => 26.0,
                'max_temperature' => 28.0,
                'active' => true
            ]
        );

        $kidsPool = Facility::firstOrCreate(
            ['name' => 'Pataugeoire'],
            [
                'type' => 'kids_pool',
                'capacity' => 20,
                'status' => 'operational',
                'volume_liters' => 50000,
                'min_temperature' => 29.0,
                'max_temperature' => 31.0,
                'active' => true
            ]
        );

        $this->call([
            PoolEquipmentSeeder::class,
            PoolChemicalSeeder::class,
        ]);
    }
}
