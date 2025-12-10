<?php

namespace App\Services;

use App\Models\Staff\Attendance;
use App\Models\Staff\Staff;
use App\Models\Staff\StaffSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AttendanceService
{
    /**
     * Process a check-in request for a staff member.
     */
    public function processCheckIn(Staff $staff, string $method)
    {
        $now = Carbon::now();
        $date = $now->toDateString();

        // 1. Check if already checked in today (and not checked out)
        $activeSession = Attendance::where('staff_id', $staff->staff_id)
            ->where('date', $date)
            ->whereNull('check_out')
            ->first();

        if ($activeSession) {
            return [
                'success' => false,
                'message' => 'Déjà pointé présent à ' . $activeSession->check_in->format('H:i'),
                'data' => $activeSession
            ];
        }

        // 2. Find applicable schedule for today
        // Note: Assuming non-recurring exact dates first, then we could fallback to weekday logic if you had a recurring table.
        // Based on schema, StaffSchedule has specific 'date'.
        $schedule = StaffSchedule::where('staff_id', $staff->staff_id)
            ->where('date', $date)
            ->first();

        $status = 'present';
        $delayMinutes = 0;

        if ($schedule) {
            $scheduledStart = Carbon::parse($schedule->date . ' ' . $schedule->start_time);
            
            // Allow 15 mins buffer
            if ($now->greaterThan($scheduledStart->addMinutes(15))) {
                $status = 'late';
                $delayMinutes = $scheduledStart->diffInMinutes($now);
            }
        }

        // 3. Create Attendance Record
        $attendance = Attendance::create([
            'staff_id' => $staff->staff_id,
            'check_in' => $now,
            'date' => $date,
            'status' => $status,
            'delay_minutes' => $delayMinutes,
            'check_in_method' => $method,
        ]);

        return [
            'success' => true,
            'message' => 'Bienvenue ' . $staff->first_name . ' ! Pointage enregistré.',
            'data' => $attendance
        ];
    }

    /**
     * Process a check-out request.
     */
    public function processCheckOut(Staff $staff)
    {
        $now = Carbon::now();
        $date = $now->toDateString();

        // Find open session
        $attendance = Attendance::where('staff_id', $staff->staff_id)
            ->whereNull('check_out')
            ->latest('check_in') // In case of multiple days open, pick latest or enforce logic
            ->first();

        if (!$attendance) {
            return [
                'success' => false,
                'message' => 'Aucune session active trouvée. Veuillez pointer l\'entrée d\'abord.'
            ];
        }

        // Calculate hours
        $checkIn = Carbon::parse($attendance->check_in);
        $durationHours = $checkIn->diffInMinutes($now) / 60;
        
        // --- Night Hours Calculation ---
        $nightHours = 0;
        $start = $checkIn->copy();
        $end = $now->copy();
        
        // Get config values
        $nightStartConfig = \App\Models\Setting::get('attendance.night_start', config('attendance.night_start', '21:00'));
        $nightEndConfig = \App\Models\Setting::get('attendance.night_end', config('attendance.night_end', '06:00'));
        $overtimeThreshold = \App\Models\Setting::get('attendance.overtime_threshold', config('attendance.overtime_threshold', 8));

        // Parse config times
        $ns = Carbon::parse($nightStartConfig);
        $ne = Carbon::parse($nightEndConfig);

        // Helper: Create Carbon instances for the specific day relative to check-in
        // Note: Night shift usually spans two days (e.g. 21:00 to 06:00 next day).
        // If night_start > night_end, it implies crossing midnight.
        // We need to set the time on the check-in date.
        
        $nightStart = $start->copy()->setTimeFrom($ns);
        $nightEnd = $start->copy()->setTimeFrom($ne);
        
        // If crossing midnight (standard case: 21:00 -> 06:00)
        if ($ns->gt($ne)) {
             $nightEnd->addDay();
        }

        // --- Logic Refinement for Overlapping ---
        // We need to calculate intersection of [CheckIn, CheckOut] with [NightStart, NightEnd]
        
        // Cap the calculation to the relevant night block
        // If check-in is way before today's night start (e.g. morning shift), we look at tonight's night block.
        // If check-in is late night (e.g. 01:00), we look at "current" night block which might have started yesterday?
        // Let's assume standard shift starting today.
        
        // Intersection Logic: Max(StartA, StartB) < Min(EndA, EndB)
        // Interval 1: Access [$start, $end]
        // Interval 2: Night [$nightStart, $nightEnd]
        
        $overlapStart = $start->max($nightStart);
        $overlapEnd = $end->min($nightEnd);
        
        if ($overlapStart->lt($overlapEnd)) {
             $nightHours += $overlapStart->diffInMinutes($overlapEnd) / 60;
        }

        // --- Overtime Calculation ---
        $overtimeHours = max(0, $durationHours - $overtimeThreshold);
        $normalHours = $durationHours - $overtimeHours; 

        $attendance->update([
            'check_out' => $now,
            'working_hours' => round($durationHours, 2),
            'night_hours' => round($nightHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            // break_minutes left as 0 for now until Pause feature added
        ]);

        return [
            'success' => true,
            'message' => 'Au revoir ' . $staff->first_name . '. Session fermée (' . round($durationHours, 2) . 'h).',
            'data' => $attendance
        ];
    }
}
