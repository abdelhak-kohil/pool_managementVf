<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity\Weekday;

class WeekdaySeeder extends Seeder
{
    /**
     * Seed weekdays in French
     */
    public function run(): void
    {
        $weekdays = [
            ['weekday_id' => 1, 'day_name' => 'Lundi'],
            ['weekday_id' => 2, 'day_name' => 'Mardi'],
            ['weekday_id' => 3, 'day_name' => 'Mercredi'],
            ['weekday_id' => 4, 'day_name' => 'Jeudi'],
            ['weekday_id' => 5, 'day_name' => 'Vendredi'],
            ['weekday_id' => 6, 'day_name' => 'Samedi'],
            ['weekday_id' => 7, 'day_name' => 'Dimanche'],
        ];

        foreach ($weekdays as $weekday) {
            Weekday::updateOrCreate(
                ['weekday_id' => $weekday['weekday_id']],
                $weekday
            );
        }

        $this->command->info('Created ' . count($weekdays) . ' weekdays.');
    }
}
