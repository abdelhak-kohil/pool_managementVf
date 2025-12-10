<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolChemical;

class PoolChemicalSeeder extends Seeder
{
    public function run()
    {
        $chemicals = [
            [
                'name' => 'Liquid Chlorine',
                'type' => 'chlorine',
                'quantity_available' => 150.00,
                'unit' => 'L',
                'minimum_threshold' => 50.00,
            ],
            [
                'name' => 'pH Minus',
                'type' => 'ph_minus',
                'quantity_available' => 75.00,
                'unit' => 'kg',
                'minimum_threshold' => 20.00,
            ],
            [
                'name' => 'pH Plus',
                'type' => 'ph_plus',
                'quantity_available' => 25.00,
                'unit' => 'kg',
                'minimum_threshold' => 10.00,
            ],
            [
                'name' => 'Flocculant Tablets',
                'type' => 'flocculant',
                'quantity_available' => 10.00,
                'unit' => 'kg',
                'minimum_threshold' => 5.00,
            ],
            [
                'name' => 'Anti-Algae',
                'type' => 'anti_algae',
                'quantity_available' => 40.00,
                'unit' => 'L',
                'minimum_threshold' => 10.00,
            ],
        ];

        foreach ($chemicals as $chem) {
            PoolChemical::create($chem);
        }
    }
}
