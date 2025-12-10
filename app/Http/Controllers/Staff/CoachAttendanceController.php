<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Admin\AccessLog;
use Carbon\Carbon;

class CoachAttendanceController extends Controller
{
    /**
     * Display daily attendance dashboard.
     */
    /**
     * Display daily attendance dashboard.
     */
    public function index(Request $request)
    {
        $startDate = $request->get('start_date', now()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $coachId = $request->get('coach_id');

        // Fetch all coaches for filter
        $coaches = Staff::coaches()->orderBy('first_name')->get();

        // Query Logs
        $query = AccessLog::with('staff')
            ->whereNotNull('staff_id')
            ->whereDate('access_time', '>=', $startDate)
            ->whereDate('access_time', '<=', $endDate);

        if ($coachId) {
            $query->where('staff_id', $coachId);
        }

        $logs = $query->orderBy('access_time', 'desc')->get();

        // Statistics
        $totalLogs = $logs->count();
        $uniqueCoaches = $logs->pluck('staff_id')->unique()->count();

        return view('admin.coaches.attendance', compact('logs', 'coaches', 'startDate', 'endDate', 'coachId', 'totalLogs', 'uniqueCoaches'));
    }
}
