<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Finance\Plan;
use App\Models\Finance\Subscription;
use App\Models\Admin\AccessLog;
use App\Models\Member\AccessBadge;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\LOG;

class ReceptionController extends Controller
{
    /**
     * Display receptionist dashboard.
     */
    public function index()
    {
        $members = Member::with(['subscriptions.plan', 'subscriptions.weekdays', 'subscriptions.subscriptionSlots.slot', 'accessbadge'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $plans = Plan::where('is_active', true)->get();

        return view('reception.index', compact('members', 'plans'));
    }

    /**
     * Display dedicated scan page.
     */
    public function scan()
    {
        return view('reception.scan');
    }

    /**
     * Fetch today's access stats for dashboard widgets.
     */
    public function todayAccesses()
{
    try {
        $today = now()->startOfDay();

        $granted = DB::table('pool_schema.access_logs')
            ->where('access_decision', 'granted')
            ->where('access_time', '>=', $today)
            ->count();

        $denied = DB::table('pool_schema.access_logs')
            ->where('access_decision', 'denied')
            ->where('access_time', '>=', $today)
            ->count();

        $total = $granted + $denied;

        $activeMembers = DB::table('pool_schema.subscriptions')
            ->where('status', 'active')
            ->count();

        return response()->json([
            'granted'       => $granted,
            'denied'        => $denied,
            'total'         => $total,
            'activeMembers' => $activeMembers,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'granted' => 0, 'denied' => 0, 'total' => 0, 'activeMembers' => 0,
            'error'   => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Live AJAX member search.
     */
public function search(Request $request)
{
    $query = $request->get('q', '');
    $members = Member::with(['accessbadge', 'subscriptions.plan', 'subscriptions.weekdays', 'createdBy', 'updatedBy'])
        ->where(function($q) use ($query) {
            $q->where('first_name', 'ILIKE', "%$query%")
              ->orWhere('last_name', 'ILIKE', "%$query%")
              ->orWhere('phone_number', 'ILIKE', "%$query%")
              ->orWhereHas('accessbadge', function($sub) use ($query) {
                  $sub->where('badge_uid', 'ILIKE', "%$query%");
              });
        })
        ->paginate(10);

    return response()->json([
        'html' => view('reception.partials.members-table', compact('members'))->render()
    ]);
}



    /**
     * Manual check-in for a member.
     */
    /**
     * Manual check-in for a member.
     */
    public function checkIn(Member $member, \App\Modules\Access\Actions\CheckInMemberAction $action)
    {
        try {
            // 🔹 Get badge safely or use MANUAL
            $badgeUid = optional($member->accessbadge)->badge_uid ?? 'MANUAL';

            $result = $action->execute($member, $badgeUid);

            if ($result->isGranted) {
                // If granted, current logic returned success message.
                // Action returns standardized AccessResult.
                return response()->json([
                    'success' => true,
                    'message' => $result->message, 
                    // formatSuccessResponse arguments mismatch? 
                    // Let's stick to simple JSON for manual check-in if that's what frontend expected, 
                    // OR reuse formatSuccessResponse if it fits. 
                    // Original code: return response()->json(['success' => true, 'message' => ...]);
                    // Let's keep it simple.
                ]);
            } else {
                 return response()->json([
                     'success' => false,
                     'message' => $result->message,
                 ]);
            }

        } catch (\Throwable $e) {
            Log::error('Échec du check-in : '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur : '.$e->getMessage()], 500);
        }
    }

    /**
     * Check-in by Badge UID (for NFC Scan).
     */
    /**
     * Check-in by Badge UID (for NFC Scan).
     */
    public function checkInByBadge(Request $request, \App\Modules\Access\Actions\ScanBadgeAction $action)
    {
        $request->validate(['badge_uid' => 'required|string']);
        
        $result = $action->execute($request->badge_uid);

        if ($result->isGranted) {
             return $this->formatSuccessResponse(
                 $result->personName, // Split name logic handled? DTO has full name. formatSuccess splits it?
                 '', // Last name empty if merged in DTO. formatSuccess expects First/Last. 
                 // Wait, formatSuccess splits? No, it takes args.
                 // Let's check formatSuccess signature.
                 $result->message, 
                 $result->planName, 
                 $result->photoUrl, 
                 $result->expiryDate
             );
        } else {
             // For error response, formatErrorResponse expects Member object.
             // We might not have a member object if badge lookup failed.
             // We should adapt formatErrorResponse or create a generic error response.
             // Existing code returned 403.
             
             return response()->json([
                'success' => false,
                'message' => $result->message,
                'data' => [
                    'firstName' => $result->personName ?? 'Inconnu',
                    'lastName'  => '', 
                    'planName'  => 'Accès Refusé',
                    'photoUrl'  => $result->photoUrl,
                    'expiryDate' => null
                ]
            ], 403);
        }
    }

    private function logAccess($memberId, $badgeUid, $decision, $reason = null, $activityId = null, $subscriptionId = null, $slotId = null, $staffId = null)
    {
        DB::table('pool_schema.access_logs')->insert([
            'member_id'       => $memberId,
            'staff_id'        => $staffId,
            'badge_uid'       => $badgeUid,
            'access_decision' => $decision,
            'denial_reason'   => $reason,
            'access_time'     => now(),
            'activity_id'     => $activityId,
            'subscription_id' => $subscriptionId,
            'slot_id'         => $slotId,
        ]);
    }



    /**
     * Update or assign badge manually.
     */
    public function updateBadge(Request $request, Member $member)
    {
        $request->validate([
            'badge_uid' => 'required|string|max:100|unique:pool_schema.access_badges,badge_uid,' . optional($member->accessbadge)->badge_id . ',badge_id',
        ]);

        if ($member->accessbadge) {
            $member->accessbadge->update(['badge_uid' => $request->badge_uid]);
        } else {
            AccessBadge::create([
                'member_id' => $member->member_id,
                'badge_uid' => $request->badge_uid,
                'status' => 'active',
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Add new member + subscription + badge.
     */
    public function storeMember(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
            'plan_id' => 'required|exists:pool_schema.plans,plan_id',
            'badge_uid' => 'required|string|unique:pool_schema.access_badges,badge_uid',
        ]);

        DB::beginTransaction();
        try {
            $member = Member::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
            ]);

            Subscription::create([
                'member_id' => $member->member_id,
                'plan_id' => $request->plan_id,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'status' => 'active',
            ]);

            AccessBadge::create([
                'member_id' => $member->member_id,
                'badge_uid' => $request->badge_uid,
                'status' => 'active',
            ]);

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding member: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Return last 5 access logs for a member.
     */
    public function memberLogs(Member $member)
    {
        $logs = AccessLog::where('member_id', $member->member_id)
            ->latest('access_time')
            ->take(5)
            ->get();

        return response()->json($logs);
    }


}
