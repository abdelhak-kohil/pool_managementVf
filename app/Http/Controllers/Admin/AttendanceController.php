<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display the attendance dashboard.
     */
    public function dashboard()
    {
        return view('attendance.dashboard');
    }

    /**
     * Fetch statistics for charts and heatmap.
     */
    public function stats(Request $request)
    {
        $period = $request->get('period', 'week'); // week, month
        $now = Carbon::now();

        if ($period === 'month') {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
        } elseif ($period === 'year') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
        } elseif ($period === 'all_years') {
            $startDate = Carbon::create(2000, 1, 1); // Far past
            $endDate = $now->copy()->endOfDay();
        } else {
            $startDate = $now->copy()->startOfWeek();
            $endDate = $now->copy()->endOfWeek();
        }

        // === 1. Total Visits (Granted Access) ===
        $totalVisits = DB::table('pool_schema.access_logs')
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->count();

        // === 2. Peak Hours (Heatmap Data) ===
        // Use a broader range for heatmap to show trends (e.g., last 30 days for week view, last 90 days for month view)
        $heatmapStartDate = $period === 'month' ? $now->copy()->subDays(90) : $now->copy()->subDays(30);
        
        $heatmapData = DB::table('pool_schema.access_logs')
            ->select(
                DB::raw("EXTRACT(DOW FROM access_time) as day_of_week"),
                DB::raw("EXTRACT(HOUR FROM access_time) as hour_of_day"),
                DB::raw("COUNT(*) as count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$heatmapStartDate, $now])
            ->groupBy('day_of_week', 'hour_of_day')
            ->get();

        // === 3. Daily Stats (Bar Chart) ===
        $dailyStats = DB::table('pool_schema.access_logs')
            ->select(
                DB::raw("DATE(access_time) as date"),
                DB::raw("COUNT(*) as count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // === 4. Most Active Days of Week ===
        $activeDays = DB::table('pool_schema.access_logs')
            ->select(
                DB::raw("TO_CHAR(access_time, 'Day') as day_name"), // Postgres specific
                DB::raw("COUNT(*) as count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->get();

        // === 5. Attendance by Activity ===
        // Complex join: AccessLog -> TimeSlot (via time & day) -> Activity
        // Note: This is an approximation based on schedule.
        $activities = DB::select("
            SELECT COALESCE(a.name, 'Hors Créneau') as name, COUNT(*) as count
            FROM pool_schema.access_logs l
            LEFT JOIN pool_schema.time_slots ts ON ts.weekday_id = EXTRACT(ISODOW FROM l.access_time)
                AND l.access_time::time BETWEEN ts.start_time AND ts.end_time
            LEFT JOIN pool_schema.activities a ON a.activity_id = COALESCE(l.activity_id, ts.activity_id)
            WHERE l.access_decision = 'granted'
            AND l.access_time BETWEEN ? AND ?
            GROUP BY COALESCE(a.name, 'Hors Créneau')
        ", [$startDate, $endDate]);

        

        // === 6. Top 10 Most Active Members ===
        $topMembers = DB::table('pool_schema.access_logs')
            ->join('pool_schema.members', 'pool_schema.members.member_id', '=', 'pool_schema.access_logs.member_id')
            ->select(
                'pool_schema.members.first_name',
                'pool_schema.members.last_name',
                DB::raw("COUNT(*) as visit_count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->groupBy('pool_schema.members.member_id', 'pool_schema.members.first_name', 'pool_schema.members.last_name')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        // === 7. Today's Slots with Occupancy ===
        $todayWeekdayId = $now->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
        $todayDate = $now->toDateString();

        $todaySlots = DB::table('pool_schema.time_slots as t')
            ->leftJoin('pool_schema.activities as a', 'a.activity_id', '=', 't.activity_id')
            ->select(
                't.slot_id',
                't.start_time',
                't.end_time',
                't.capacity',
                'a.name as activity_name',
                'a.color_code'
            )
            ->where('t.weekday_id', $todayWeekdayId)
            ->orderBy('t.start_time')
            ->get();

        // Calculate occupancy for each slot
        foreach ($todaySlots as $slot) {
            $slot->attendees = DB::table('pool_schema.access_logs')
                ->where('access_decision', 'granted')
                ->whereDate('access_time', $todayDate)
                ->whereRaw("access_time::time BETWEEN ? AND ?", [$slot->start_time, $slot->end_time])
                ->count();
            
            // Calculate percentage
            $slot->percentage = $slot->capacity > 0 ? round(($slot->attendees / $slot->capacity) * 100) : 0;
        }

        // Identify Current Slot
        $currentTime = $now->format('H:i:s');
        $currentSlot = $todaySlots->first(function ($slot) use ($currentTime) {
            return $currentTime >= $slot->start_time && $currentTime <= $slot->end_time;
        });

        return response()->json([
            'totalVisits' => $totalVisits,
            'heatmap' => $heatmapData,
            'daily' => $dailyStats,
            'activeDays' => $activeDays,
            'activities' => $activities,
            'topMembers' => $topMembers,
            'todaySlots' => $todaySlots,
            'currentSlot' => $currentSlot,
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ]
        ]);
    }

    /**
     * Export attendance report to PDF.
     */
    public function exportPdf(Request $request)
    {
        $from = $request->get('from', Carbon::now()->startOfMonth()->toDateString());
        $to = $request->get('to', Carbon::now()->endOfMonth()->toDateString());
        
        // Ensure dates are Carbon objects for comparison if needed, but string is fine for query
        $startDate = Carbon::parse($from)->startOfDay();
        $endDate = Carbon::parse($to)->endOfDay();

        // 1. Logs
        $logs = DB::table('pool_schema.access_logs')
            ->join('pool_schema.members', 'pool_schema.members.member_id', '=', 'pool_schema.access_logs.member_id')
            ->select(
                'pool_schema.access_logs.*',
                'pool_schema.members.first_name',
                'pool_schema.members.last_name'
            )
            ->whereBetween('access_time', [$startDate, $endDate])
            ->orderBy('access_time', 'desc')
            ->get();

        // 2. Stats for PDF
        $totalVisits = $logs->where('access_decision', 'granted')->count();

        $activeDays = DB::table('pool_schema.access_logs')
            ->select(
                DB::raw("TO_CHAR(access_time, 'Day') as day_name"),
                DB::raw("COUNT(*) as count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $activities = DB::select("
            SELECT COALESCE(a.name, 'Hors Créneau') as name, COUNT(*) as count
            FROM pool_schema.access_logs l
            LEFT JOIN pool_schema.time_slots ts ON ts.weekday_id = EXTRACT(ISODOW FROM l.access_time)
                AND l.access_time::time BETWEEN ts.start_time AND ts.end_time
            LEFT JOIN pool_schema.activities a ON a.activity_id = COALESCE(l.activity_id, ts.activity_id)
            WHERE l.access_decision = 'granted'
            AND l.access_time BETWEEN ? AND ?
            GROUP BY COALESCE(a.name, 'Hors Créneau')
        ", [$startDate, $endDate]);

        $topMembers = DB::table('pool_schema.access_logs')
            ->join('pool_schema.members', 'pool_schema.members.member_id', '=', 'pool_schema.access_logs.member_id')
            ->select(
                'pool_schema.members.first_name',
                'pool_schema.members.last_name',
                DB::raw("COUNT(*) as visit_count")
            )
            ->where('access_decision', 'granted')
            ->whereBetween('access_time', [$startDate, $endDate])
            ->groupBy('pool_schema.members.member_id', 'pool_schema.members.first_name', 'pool_schema.members.last_name')
            ->orderByDesc('visit_count')
            ->limit(20)
            ->get();

        $pdf = Pdf::loadView('exports.attendance', compact('logs', 'from', 'to', 'totalVisits', 'activeDays', 'activities', 'topMembers'));
        return $pdf->download('rapport_presence.pdf');
    }
}
