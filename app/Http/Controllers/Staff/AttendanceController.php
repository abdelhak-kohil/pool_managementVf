<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Attendance;
use App\Models\Staff\Staff;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display the daily attendance dashboard.
     */
    public function index()
    {
        $today = Carbon::today();
        
        // Fetch all attendances for today
        $attendances = Attendance::with('staff')
            ->where('date', $today->toDateString())
            ->orderBy('check_in', 'desc')
            ->get();

        // Calculate basic stats
        $stats = [
            'present' => $attendances->whereNull('check_out')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'total' => $attendances->count(),
        ];

        // 1. Weekly Trends (Last 7 Days)
        $weeklyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $count = Attendance::whereDate('date', $date)->count();
            $weeklyStats['labels'][] = $date->format('d/m');
            $weeklyStats['data'][] = $count;
        }

        // 2. Lateness Distribution (Current Month)
        $monthQuery = Attendance::whereMonth('date', Carbon::now()->month)->whereYear('date', Carbon::now()->year);
        $totalMonth = $monthQuery->count();
        $lateMonth = $monthQuery->where('status', 'late')->count();
        $onTimeMonth = $monthQuery->where('status', 'present')->count(); // Assuming 'present' implies on time if not 'late'
        // Note: 'status' can be 'present', 'late', 'absent'. 
        // If we want accurate 'On Time', it's present - late? 
        // Actually, status is stored as 'present' OR 'late'. 
        // So 'present' usually means on time in this system logic (AttendanceService).
        
        $latenessStats = [
            'late' => $lateMonth,
            'on_time' => $onTimeMonth,
            'absent' => 0 // Absent logic is not fully implemented in DB yet (scheduled job needed), keeping 0 for now or calculating if possible
        ];

        return view('staff.attendance.dashboard', compact('attendances', 'stats', 'weeklyStats', 'latenessStats'));
    }

    /**
     * Display the Pointage Kiosk view.
     */
    public function pointage()
    {
        return view('staff.attendance.pointage');
    }

    /**
     * Handle the check-in/out action from Kiosk.
     */
    public function storePointage(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string', // Can be badge_uid or username
            'password' => 'nullable|string', // Only if username is used
        ]);

        $identifier = $request->input('identifier');
        $staff = null;

        // 1. Try to find by Badge UID (assuming we have access_badges table logic linked or directly on staff)
        // Wait, AccessBadge table links badge_uid to staff_id.
        // Let's assume we search properly.
        $staff = Staff::whereHas('badges', function($q) use ($identifier) {
            $q->where('badge_uid', $identifier)->where('status', 'active');
        })->first();

        // 2. Fallback: Find by Username (PIN mode usually sends username/pin)
        if (!$staff) {
            $staff = Staff::where('username', $identifier)->first();
            if ($staff && $request->filled('password')) {
                if (!Hash::check($request->input('password'), $staff->password_hash)) {
                    return response()->json(['success' => false, 'message' => 'Mot de passe incorrect.'], 401);
                }
            } elseif ($staff) {
                 // If found by username but no password provided (and not badge), reject if strict mode.
                 // For now, assuming Kiosk might send just username for RFID simulation if configured so? 
                 // No, standard is Badge OR User+Pass.
                 return response()->json(['success' => false, 'message' => 'Authentification requise.'], 401);
            }
        }

        if (!$staff) {
            return response()->json(['success' => false, 'message' => 'Identifiant non reconnu.'], 404);
        }

        // 3. Determine Action: Check-in or Check-out?
        // Logic: If user has an open session -> Check Out. Else -> Check In.
        $openSession = Attendance::where('staff_id', $staff->staff_id)
            ->whereNull('check_out')
            ->latest('check_in')
            ->first();

        if ($openSession) {
            // Action: Check Out
            $result = $this->attendanceService->processCheckOut($staff);
        } else {
            // Action: Check In
            $method = $request->filled('password') ? 'pin' : 'rfid';
            $result = $this->attendanceService->processCheckIn($staff, $method);
        }

        return response()->json($result);
    }
}
