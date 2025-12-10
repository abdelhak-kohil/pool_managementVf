<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\Facility;

class FacilitySeeder extends Seeder
{
    public function run()
    {
        $facilities = [
            [
                'name' => 'Grand Bassin Olympique',
                'type' => 'main_pool',
                'capacity' => 200,
                'status' => 'operational',
                'volume_liters' => 2500000,
                'min_temperature' => 26.0,
                'max_temperature' => 28.0,
                'active' => true
            ],
            [
                'name' => 'Petit Bassin d\'Apprentissage',
                'type' => 'learning_pool',
                'capacity' => 50,
                'status' => 'operational',
                'volume_liters' => 500000,
                'min_temperature' => 28.0,
                'max_temperature' => 30.0,
                'active' => true
            ],
            [
                'name' => 'Pataugeoire Ludique',
                'type' => 'kids_pool',
                'capacity' => 30,
                'status' => 'operational',
                'volume_liters' => 50000,
                'min_temperature' => 30.0,
                'max_temperature' => 32.0,
                'active' => true
            ],
            [
                'name' => 'Jacuzzi Intérieur',
                'type' => 'jacuzzi',
                'capacity' => 8,
                'status' => 'operational',
                'volume_liters' => 2000,
                'min_temperature' => 36.0,
                'max_temperature' => 38.0,
                'active' => true
            ],
            [
                'name' => 'Jacuzzi Extérieur',
                'type' => 'jacuzzi',
                'capacity' => 10,
                'status' => 'under_maintenance',
                'volume_liters' => 2500,
                'min_temperature' => 35.0,
                'max_temperature' => 37.0,
                'active' => false
            ],
            [
                'name' => 'Sauna Finlandais',
                'type' => 'sauna',
                'capacity' => 12,
                'status' => 'operational',
                'volume_liters' => 0, // Not applicable
                'min_temperature' => 80.0,
                'max_temperature' => 90.0,
                'active' => true
            ],
            [
                'name' => 'Hammam Oriental',
                'type' => 'hammam',
                'capacity' => 15,
                'status' => 'operational',
                'volume_liters' => 0, // Not applicable
                'min_temperature' => 40.0,
                'max_temperature' => 50.0,
                'active' => true
            ],
            [
                'name' => 'Toboggan "Le Grand Bleu"',
                'type' => 'slide_pool',
                'capacity' => 1, // Per slide
                'status' => 'operational',
                'volume_liters' => 10000, // Landing pool
                'min_temperature' => 26.0,
                'max_temperature' => 28.0,
                'active' => true
            ],
        ];

        foreach ($facilities as $data) {
            Facility::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
