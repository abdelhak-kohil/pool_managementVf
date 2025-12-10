<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity\TimeSlot;
use App\Models\Activity\Activity;
use App\Models\Member\PartnerGroup;
use App\Models\Staff\Staff;
use App\Models\Activity\Weekday;
use Carbon\Carbon;

class TimeSlotSeeder extends Seeder
{
    /**
     * Seed Weekly Planning / Créneaux
     */
    public function run(): void
    {
        $activities = Activity::all();
        $coaches = Staff::whereHas('role', function($q) {
            $q->where('role_name', 'coach');
        })->get();
        // If no coaches found via role, try getting any staff as fallback or create dummy
        if ($coaches->isEmpty()) {
            $coaches = Staff::limit(5)->get(); 
        }

        $weekdays = Weekday::all();
        $partnerGroups = PartnerGroup::all();

        // Planning logic:
        // Weekdays (Sun-Thu usually in some regions, or Mon-Sun)
        // Let's assume Mon-Sun (1-7)
        
        $schedule = [
            // Morning
            ['start' => '06:00:00', 'end' => '08:00:00', 'activity' => 'Natation Libre', 'capacity' => 50],
            ['start' => '08:00:00', 'end' => '09:00:00', 'activity' => 'Aquagym', 'capacity' => 25],
            ['start' => '09:00:00', 'end' => '10:00:00', 'activity' => 'Cours de Natation Débutant', 'capacity' => 15],
            
            // Late Morning/Noon
            ['start' => '10:00:00', 'end' => '12:00:00', 'activity' => 'Réservation Groupe/École', 'capacity' => 40],
            ['start' => '12:00:00', 'end' => '14:00:00', 'activity' => 'Natation Libre', 'capacity' => 50],
            
            // Afternoon
            ['start' => '14:00:00', 'end' => '16:00:00', 'activity' => 'Natation Thérapeutique', 'capacity' => 20],
            ['start' => '16:00:00', 'end' => '17:00:00', 'activity' => 'Bébés Nageurs', 'capacity' => 15],
            
            // Evening
            ['start' => '17:00:00', 'end' => '18:00:00', 'activity' => 'Natation Enfants (7-12 ans)', 'capacity' => 20],
            ['start' => '18:00:00', 'end' => '19:00:00', 'activity' => 'Aquabike', 'capacity' => 20],
            ['start' => '19:00:00', 'end' => '20:30:00', 'activity' => 'Club Compétition', 'capacity' => 30],
            ['start' => '20:30:00', 'end' => '22:00:00', 'activity' => 'Natation Libre', 'capacity' => 50],
        ];

        // Specific weekend schedule
        $weekendSchedule = [
             ['start' => '08:00:00', 'end' => '12:00:00', 'activity' => 'Natation Libre', 'capacity' => 60],
             ['start' => '14:00:00', 'end' => '18:00:00', 'activity' => 'Natation Libre', 'capacity' => 60],
        ];

        foreach ($weekdays as $weekday) {
            $isWeekend = in_array($weekday->weekday_id, [5, 6]); // Fri-Sat weekend? Or Sat-Sun [6,7]? Let's assume Sat-Sun [6,7] for standard
            // In Algeria Fri-Sat is weekend, in France Sat-Sun. Assuming generic/France: 6,7
            $currentSchedule = ($weekday->weekday_id >= 6) ? $weekendSchedule : $schedule;

            foreach ($currentSchedule as $slotData) {
                // Find activity
                $activity = $activities->firstWhere('name', $slotData['activity']);
                if (!$activity) continue;

                // Assign random coach if needed (for classes/lessons)
                $coachId = null;
                if (in_array($activity->access_type, ['lesson', 'class', 'club'])) {
                    if ($coaches->isNotEmpty()) {
                        $coachId = $coaches->random()->staff_id;
                    }
                }

                // Assign group Name if it's a group activity
                $assignedGroup = null;
                if ($activity->name === 'Réservation Groupe/École' && $partnerGroups->isNotEmpty()) {
                    $assignedGroup = $partnerGroups->random()->name;
                }

                TimeSlot::create([
                    'weekday_id' => $weekday->weekday_id,
                    'start_time' => $slotData['start'],
                    'end_time'   => $slotData['end'],
                    'activity_id' => $activity->activity_id,
                    'assigned_group' => $assignedGroup,
                    'capacity'   => $slotData['capacity'],
                    'coach_id'   => $coachId,
                    'is_blocked' => false,
                    'created_by' => 1, // Admin
                ]);
            }
        }

        $this->command->info('Created Weekly Planning (Créneaux).');
    }
}
