<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Member\AccessBadge;
use Faker\Factory as Faker;

class FreeBadgesSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Create 20 free badges
        for ($i = 0; $i < 20; $i++) {
            $badgeUid = strtoupper($faker->bothify('FREE-####-????'));
            
            AccessBadge::create([
                'member_id' => null,
                'staff_id' => null,
                'badge_uid' => $badgeUid,
                'status' => 'active', // Ready to be assigned
                'issued_at' => now(),
            ]);

            $this->command->info("Created Free Badge: {$badgeUid}");
        }
    }
}
