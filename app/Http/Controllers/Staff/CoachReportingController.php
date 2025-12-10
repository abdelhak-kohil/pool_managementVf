<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Activity\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CoachReportExport;
use App\Exports\GlobalCoachReportExport;

class CoachReportingController extends Controller
{
    /**
     * Show the reporting dashboard.
     */
    public function index()
    {
        $coaches = Staff::coaches()->get();
        return view('admin.coaches.reports', compact('coaches'));
    }

    /**
     * Generate a PDF report for a specific coach.
     */
    /**
     * Generate a PDF report for a specific coach.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->calculateReportData($request);
        $data['date'] = now()->format('d/m/Y');
        
        $pdf = Pdf::loadView('exports.coach_report', $data);
        return $pdf->download("rapport_coach_{$data['coach']->last_name}_{$data['month']}_{$data['year']}.pdf");
    }

    /**
     * Generate an Excel report for a specific coach.
     */
    public function exportExcel(Request $request)
    {
        $data = $this->calculateReportData($request);
        $data['date'] = now()->format('d/m/Y');
        
        return Excel::download(new CoachReportExport($data), "rapport_coach_{$data['coach']->last_name}_{$data['month']}_{$data['year']}.xlsx");
    }

    /**
     * Return report data for preview modal (JSON).
     */
    public function preview(Request $request)
    {
        $data = $this->calculateReportData($request);
        // Return JSON for the modal
        return response()->json([
            'coach_name' => $data['coach']->full_name,
            'period' => \Carbon\Carbon::createFromDate(null, $data['month'], 1)->locale('fr')->monthName . ' ' . $data['year'],
            'sessions_count' => $data['sessionsCount'],
            'total_hours' => $data['totalHours'],
            'salary' => number_format($data['salary'], 2) . ' DZD',
            'sessions' => $data['sessions']
        ]);
    }

    /**
     * Generate a global Excel report for all coaches.
     */
    public function exportGlobalExcel(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $coaches = Staff::coaches()->where('is_active', true)->get();
        $reportData = [];
        $grandTotalSalary = 0;
        $grandTotalHours = 0;

        foreach ($coaches as $coach) {
            $coachRequest = new Request([
                'coach_id' => $coach->staff_id,
                'month' => $month,
                'year' => $year
            ]);

            $data = $this->calculateReportData($coachRequest);
            
            $reportData[] = [
                'coach_name' => $coach->full_name,
                'salary_type' => $coach->salary_type,
                'sessions_count' => $data['sessionsCount'],
                'total_hours' => $data['totalHours'],
                'salary' => $data['salary'],
                'calculation_details' => $data['calculation_details'] ?? ''
            ];

            $grandTotalSalary += $data['salary'];
            $grandTotalHours += $data['totalHours'];
        }

        $excelData = [
            'month' => $month,
            'year' => $year,
            'reports' => $reportData,
            'grandTotalSalary' => $grandTotalSalary,
            'grandTotalHours' => $grandTotalHours
        ];

        return Excel::download(new GlobalCoachReportExport($excelData), "rapport_global_coachs_{$month}_{$year}.xlsx");
    }

    /**
     * Generate a global PDF report for all coaches.
     */
    public function exportGlobalPdf(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $coaches = Staff::coaches()->where('is_active', true)->get();
        $reportData = [];
        $grandTotalSalary = 0;
        $grandTotalHours = 0;

        foreach ($coaches as $coach) {
            // Reuse the existing calculation logic
            // We need to mock a request or extract logic further, but for now we can create a new Request
            $coachRequest = new Request([
                'coach_id' => $coach->staff_id,
                'month' => $month,
                'year' => $year
            ]);

            $data = $this->calculateReportData($coachRequest);
            
            $reportData[] = [
                'coach_name' => $coach->full_name,
                'salary_type' => $coach->salary_type,
                'sessions_count' => $data['sessionsCount'],
                'total_hours' => $data['totalHours'],
                'salary' => $data['salary'],
                'calculation_details' => $data['calculation_details'] ?? ''
            ];

            $grandTotalSalary += $data['salary'];
            $grandTotalHours += $data['totalHours'];
        }

        $pdfData = [
            'month' => $month,
            'year' => $year,
            'date' => now()->format('d/m/Y'),
            'reports' => $reportData,
            'grandTotalSalary' => $grandTotalSalary,
            'grandTotalHours' => $grandTotalHours
        ];

        $pdf = Pdf::loadView('exports.global_coach_report', $pdfData);
        return $pdf->download("rapport_global_coachs_{$month}_{$year}.pdf");
    }

    /**
     * Shared logic to calculate report data.
     */
    private function calculateReportData(Request $request)
    {
        $coachId = $request->get('coach_id');
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $coach = Staff::findOrFail($coachId);

        // 1. Sessions handled
        $slots = TimeSlot::where('coach_id', $coachId)
            ->with(['activity', 'weekday'])
            ->get();


         
        // 2. Get Attendance Logs (Logic from CoachAttendanceController)
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth()->endOfDay();

        $logs = \App\Models\Admin\AccessLog::where('staff_id', $coachId)
            ->whereBetween('access_time', [$startDate, $endDate])
            ->orderBy('access_time', 'asc')
            ->get();

        
        // 3. Match Logs to Planning Slots
        $totalHours = 0;
        $sessionsCount = 0;
        $verifiedSessions = [];
        $processedSlots = []; // To avoid double counting if multiple logs for same slot

        foreach ($logs as $log) {
            $logTime = $log->access_time;
            $dayOfWeek = $logTime->dayOfWeekIso;
             Log::error('//  $dayOfWeek  ' . $dayOfWeek);
             Log::error('//  $logTime   ' . $logTime);
            // Find a matching slot for this log
            // We look for the closest slot on this weekday
            $dailySlots = $slots->where('weekday_id', $dayOfWeek);
            Log::error('//dailySlots   ' . $dailySlots);
            
            $matchingSlot = null;
            $minDiff = 25; // Allow up to 90 minutes difference

            foreach ($dailySlots as $slot) {
                $expectedStart = $logTime->copy()->setTimeFromTimeString($slot->start_time);
                Log::error('//expectedStart   ' . $expectedStart);
                $diffInMinutes = abs($logTime->diffInMinutes($expectedStart, false));
                Log::error('//diffInMinutes   ' . $diffInMinutes);

                if ($diffInMinutes <= $minDiff) {
                    $minDiff = $diffInMinutes;
                    $matchingSlot = $slot;
                }
            }


            
            if ($matchingSlot) {
                // Create a unique key for this session (Date + SlotID)
                $sessionKey = $logTime->format('Y-m-d') . '-' . $matchingSlot->id;

                if (!in_array($sessionKey, $processedSlots)) {
                    $slotStart = \Carbon\Carbon::parse($matchingSlot->start_time);
                    $slotEnd = \Carbon\Carbon::parse($matchingSlot->end_time);
                    $duration = $slotEnd->diffInHours($slotStart);

                    $totalHours += $duration;
                    $sessionsCount++;
                    $processedSlots[] = $sessionKey;

                    $verifiedSessions[] = [
                        'date' => $logTime->format('d/m/Y'),
                        'day_name' => $logTime->locale('fr')->dayName,
                        'start_time' => $matchingSlot->start_time,
                        'end_time' => $matchingSlot->end_time,
                        'activity' => $matchingSlot->activity->name,
                        'duration' => $duration,
                        'timestamp' => $logTime->timestamp
                    ];
                }
            }
        }

        // Sort sessions by timestamp
        usort($verifiedSessions, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });

        // 3. Salary Estimation
        $salary = 0;
        $calculationDetails = '';

        if ($coach->salary_type === 'fixed') {
            $salary = $coach->hourly_rate; 
            $calculationDetails = 'Salaire Fixe : ' . number_format($salary, 2) . ' DZD';
        } elseif ($coach->salary_type === 'per_hour') {
            $salary = $totalHours * $coach->hourly_rate;
            $calculationDetails = "{$totalHours} heures x " . number_format($coach->hourly_rate, 2) . " DZD/heure";
        } elseif ($coach->salary_type === 'per_session') {
            $salary = $sessionsCount * $coach->hourly_rate;
            $calculationDetails = "{$sessionsCount} séances x " . number_format($coach->hourly_rate, 2) . " DZD/séance";
        }

        return [
            'coach' => $coach,
            'slots' => $slots,
            'sessions' => $verifiedSessions,
            'totalHours' => $totalHours,
            'salary' => $salary,
            'calculation_details' => $calculationDetails,
            'month' => $month,
            'year' => $year,
            'sessionsCount' => $sessionsCount
        ];
    }
}
