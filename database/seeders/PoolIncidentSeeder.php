<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pool\PoolIncident;
use App\Models\Pool\Facility;
use App\Models\Staff\Staff;

class PoolIncidentSeeder extends Seeder
{
    public function run()
    {
        $pool = Facility::where('type', 'main_pool')->first();
        $staff = Staff::first();

        if (!$pool || !$staff) return;

        PoolIncident::create([
            'pool_id' => $pool->facility_id,
            'created_by' => $staff->staff_id,
            'title' => 'Glass broken near pool edge', // Added title
            'severity' => 'high',
            'description' => 'Glass broken near pool edge.',
            'status' => 'resolved',
            'created_at' => now()->subDays(10), // reported_at -> created_at
            'updated_at' => now()->subDays(10)->addHours(2), // resolved_at -> updated_at
        ]);

        PoolIncident::create([
            'pool_id' => $pool->facility_id,
            'created_by' => $staff->staff_id,
            'title' => 'Ladder loose', // Added title
            'severity' => 'medium',
            'description' => 'Ladder loose.',
            'status' => 'open',
            'created_at' => now()->subDay(),
        ]);
    }
}
