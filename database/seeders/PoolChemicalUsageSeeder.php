<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolChemicalUsage;
use App\Models\Pool\PoolChemical;
use App\Models\Pool\Facility;
use App\Models\Staff\Staff;

class PoolChemicalUsageSeeder extends Seeder
{
    public function run()
    {
        $chemical = PoolChemical::first();
        $pool = Facility::first();
        $staff = Staff::first();

        if (!$chemical || !$pool || !$staff) return;

        PoolChemicalUsage::create([
            'chemical_id' => $chemical->chemical_id,
            // 'pool_id' => $pool->facility_id, // pool_id not in schema, only chemical_id and technician_id
            'technician_id' => $staff->staff_id,
            'quantity_used' => 5.0,
            'quantity_used' => 5.0,
            'purpose' => 'Shock treatment', // reason -> purpose
            'usage_date' => now()->subDays(2), // used_at -> usage_date
        ]);
    }
}
