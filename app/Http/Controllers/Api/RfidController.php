<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Staff\Attendance;
use App\Models\Member; // Assuming Member model exists
use App\Models\AccessBadge; // Assuming AccessBadge model exists
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RfidController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Handle Member Check-In via RFID
     */
    public function checkInMember(Request $request)
    {
        $request->validate([
            'badge_uid' => 'required|string',
            'reader_id' => 'nullable|string',
        ]);

        $badgeUid = $request->input('badge_uid');

        // 1. Find the Badge and Member
        // Assuming badges are linked to members. 
        // We might need to adjust this depending on actual schema (access_badges table).
        // For now, let's assume AccessBadge model links to member_id.
        
        $badge = DB::table('pool_schema.access_badges')
            ->where('badge_uid', $badgeUid)
            ->where('status', 'active')
            ->first();

        if (!$badge || !$badge->member_id) {
            return response()->json([
                'success' => false,
                'message' => 'Badge non reconnu ou non assigné.',
                'code' => 404
            ], 404);
        }

        $member = Member::find($badge->member_id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Membre introuvable.',
                'code' => 404
            ], 404);
        }

        // 2. Check Member Status
        if (!$member->is_active) { // Assuming is_active column exists
            return response()->json([
                'success' => false,
                'message' => 'Compte membre inactif.',
                'code' => 403
            ], 403);
        }

        // 3. Check Subscription Status
        $activeSub = DB::table('pool_schema.subscriptions')
            ->where('member_id', $member->member_id)
            ->where('status', 'active')
            ->where('start_date', '<=', Carbon::now())
            ->where('end_date', '>=', Carbon::now())
            ->first();

        if (!$activeSub) {
            return response()->json([
                'success' => false,
                'member_name' => $member->first_name . ' ' . $member->last_name,
                'message' => 'Aucun abonnement actif.',
                'code' => 403
            ], 403);
        }

        // 4. Record Attendance (Log)
        DB::table('pool_schema.access_logs')->insert([
            'badge_uid' => $badgeUid,
            'member_id' => $member->member_id,
            'access_time' => Carbon::now(),
            'action_type' => 'check_in',
            'location' => $request->input('reader_id', 'RECEPTION'),
            'status' => 'granted'
        ]);

        return response()->json([
            'success' => true,
            'member_name' => $member->first_name . ' ' . $member->last_name,
            'photo_url' => $member->photo_url ?? null, // Assuming photo_url
            'subscription_status' => 'active',
            'message' => 'Bienvenue, ' . $member->first_name,
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * Handle Staff Check-In via RFID
     */
    public function checkInStaff(Request $request)
    {
        $request->validate([
            'badge_uid' => 'required|string',
        ]);

        $badgeUid = $request->input('badge_uid');

        // 1. Find Staff by Badge
        // Staff might have relationship via `badges` table or `card_number` column.
        // Based on previous files, Staff has `badges` relationship.
        $staff = Staff::whereHas('badges', function($q) use ($badgeUid) {
            $q->where('badge_uid', $badgeUid)->where('status', 'active');
        })->first();

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Badge staff inconnu.',
                'code' => 404
            ], 404);
        }

        // 2. Logic: Check In or Out?
        // Reuse AttendanceService logic which toggles.
        // We need to assume 'rfid' method.
        
        // Check if currently checked in
        $lastLog = Attendance::where('staff_id', $staff->staff_id)
            ->whereNull('check_out')
            ->orderBy('check_in', 'desc')
            ->first();

        if ($lastLog) {
            // Process Output
            $result = $this->attendanceService->processCheckOut($staff);
            $action = 'check_out';
        } else {
            // Process Input
            $result = $this->attendanceService->processCheckIn($staff, 'rfid');
            $action = 'check_in';
        }

        if (!$result['success']) {
             return response()->json([
                'success' => false,
                'message' => $result['message'],
                'code' => 500
            ], 500);
        }

        return response()->json([
            'success' => true,
            'staff_name' => $staff->full_name,
            'action' => $action,
            'message' => $result['message'],
            'timestamp' => Carbon::now()->toDateTimeString()
        ]);
    }

    /**
     * Get Member History
     */
    public function memberHistory($id)
    {
        $logs = DB::table('pool_schema.access_logs')
            ->where('member_id', $id)
            ->orderBy('access_time', 'desc')
            ->limit(20)
            ->get();

        return response()->json($logs);
    }

     /**
     * Get Staff History
     */
    public function staffHistory($id)
    {
        $logs = Attendance::where('staff_id', $id)
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get();

        return response()->json($logs);
    }
}
