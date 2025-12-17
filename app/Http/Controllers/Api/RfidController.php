<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Staff\Staff;
use App\Models\Staff\Attendance;
use App\Models\Member\Member;
use App\Models\Member\AccessBadge;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RfidController extends Controller
{
    /**
     * Handle Member/Staff Check-In via RFID.
     * Matches logic from ReceptionController::checkInByBadge
     */
    /**
     * Handle Member/Staff Check-In via RFID.
     */
    public function checkInMember(Request $request, \App\Modules\Access\Actions\ScanBadgeAction $action)
    {
        $request->validate([
            'badge_uid' => 'required|string',
            'reader_id' => 'nullable|string',
        ]);

        $badgeUid = $request->input('badge_uid');
        $readerId = $request->input('reader_id', 'API');

        try {
            $result = $action->execute($badgeUid);

            if ($result->isGranted) {
                // Success: Return expected Client Format
                // The client likely expects: { success: true, message: ..., data: {...} }
                // We should reuse the format methods if possible, but they are private.
                // Or recreate response. Existing code used formatSuccessResponse.
                
                // Let's create a response compatible with the client.
                return response()->json([
                    'success' => true,
                    'message' => $result->message,
                    'data' => [
                        'firstName' => explode(' ', $result->personName)[0] ?? '',
                        'lastName' =>  explode(' ', $result->personName)[1] ?? '',
                        'planName' => $result->planName ?? $result->personType, 
                        'photoUrl' => $result->photoUrl,
                        'expiryDate' => $result->expiryDate ? Carbon::parse($result->expiryDate)->toDateString() : 'Active',
                        'remainingSessions' => $result->remainingSessions,
                        // Pass raw result data in case it's a group or other special type
                        'raw' => $result
                    ]
                ]);

            } else {
                 return response()->json([
                    'success' => false,
                    'message' => $result->message
                ], 403);
            }

        } catch (\Exception $e) {
            Log::error("Error processing RFID scan: " . $e->getMessage());
             return response()->json([
                'success' => false,
                'message' => 'Erreur système: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkInGroup(Request $request, \App\Modules\Access\Actions\CheckInGroupAction $action)
    {
        try {
            $request->validate([
                'badge_uid' => 'required|string',
                'attendees' => 'required|integer|min:1',
                'reader_id' => 'nullable|string',
            ]);

            $badgeUid = $request->input('badge_uid');
            $attendees = $request->input('attendees');
            $readerId = $request->input('reader_id', 'API');
            
            // Find badge and staff (assume current user or override)
            $badge = AccessBadge::where('badge_uid', $badgeUid)->first();
            if (!$badge) {
                return response()->json(['success' => false, 'message' => 'Badge introuvable.'], 404);
            }

            // Ideally, we need the Staff ID. For API usage, Auth::id() should work if authenticated as staff.
            // Or passed via request. For now, assume generic staff or Auth.
            // If not authenticated, we might need a default staff or error.
            $staff = auth()->user() ? (auth()->user()->staff ?? Staff::first()) : Staff::first(); 

            if (!$staff) {
                 return response()->json(['success' => false, 'message' => 'Staff non identifié (Aucun profil Staff trouvé).'], 403);
            }

            $result = $action->execute($badge, $staff, $attendees);

            if ($result->isGranted) {
                 return response()->json([
                    'success' => true,
                    'message' => $result->message,
                    'data' => [
                        'firstName' => $result->personName,
                        'lastName' => '', // Groups generally just have a single name
                        'planName' => $result->planName,
                        'photoUrl' => $result->photoUrl,
                        'expiryDate' => null,
                        'remainingSessions' => $result->remainingSessions
                    ]
                ]);
            } else {
                 return response()->json([
                    'success' => false,
                    'message' => $result->message,
                    'data' => [
                        'firstName' => $result->personName, // Show group name even on error
                        'planName' => 'Refusé'
                    ]
                ], 403);
            }

        } catch (\Throwable $e) {
            Log::error("RFID Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process Member Check-In
     */
    private function processMemberCheckIn(Member $member, $badgeUid, $readerId)
    {
        // 1. Check Subscription
        $activeSub = DB::table('pool_schema.subscriptions as s')
            ->join('pool_schema.plans as p', 'p.plan_id', '=', 's.plan_id')
            ->join('pool_schema.activities as a', 'a.activity_id', '=', 's.activity_id')
            ->where('s.member_id', $member->member_id)
            ->where('s.status', 'active')
            ->whereDate('s.start_date', '<=', now())
            ->whereDate('s.end_date', '>=', now())
            ->select('s.*', 'p.plan_type', 'p.plan_name', 'a.activity_id')
            ->first();

        if (!$activeSub) {
            $this->logAccess($member->member_id, $badgeUid, 'denied', 'Abonnement inactif/expiré');
            return $this->formatErrorResponse($member, "Aucun abonnement actif.");
        }

        // 2. Time/Date Logic
        $dayMap = [
            'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche',
        ];
        $todayFr = $dayMap[now()->format('l')] ?? now()->format('l');
        $todayId = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->value('weekday_id');
        $currentTime = now()->format('H:i:s');

        $currentSlot = DB::table('pool_schema.time_slots')
            ->where('end_time', '>=', $currentTime)
            ->where('start_time', '<=', $currentTime)
            ->where('weekday_id', $todayId)
            ->first();

        // 3. Maintenance Restriction
        if ($currentSlot) {
            $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
            if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                $this->logAccess($member->member_id, $badgeUid, 'denied', 'Activité maintenance');
                return $this->formatErrorResponse($member, "Activité 'Entretien' en cours.");
            }
        }

        // 4. Capacity Check
        if ($currentSlot && $currentSlot->capacity) {
            $attendeesCount = DB::table('pool_schema.access_logs')
                ->where('access_decision', 'granted')
                ->whereDate('access_time', now())
                ->whereTime('access_time', '>=', $currentSlot->start_time)
                ->whereTime('access_time', '<=', $currentSlot->end_time)
                ->count();

            if ($attendeesCount >= $currentSlot->capacity) {
                $this->logAccess($member->member_id, $badgeUid, 'denied', 'Capacité atteinte', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id);
                return $this->formatErrorResponse($member, "Créneau complet.");
            }
        }

        // 5. Monthly/Weekly Day Restriction
        if ($activeSub->plan_type === 'monthly_weekly') {
            $todayRecord = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->first();
            if ($todayRecord) {
                $allowedDays = DB::table('pool_schema.subscription_allowed_days')
                    ->where('subscription_id', $activeSub->subscription_id)
                    ->pluck('weekday_id')
                    ->toArray();

                if (!in_array($todayRecord->weekday_id, $allowedDays)) {
                    $this->logAccess($member->member_id, $badgeUid, 'denied', 'Jour non autorisé', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id ?? null);
                    return $this->formatErrorResponse($member, "Jour non autorisé.");
                }
            }
        }

        // 6. Slot Specific Restrictions
        if ($currentSlot) {
            $allowedSlotIds = DB::table('pool_schema.subscription_slots')
                ->where('subscription_id', $activeSub->subscription_id)
                ->pluck('slot_id')
                ->toArray();

            // Duplicate Check
            $alreadyIn = DB::table('pool_schema.access_logs')
                ->where('member_id', $member->member_id)
                ->where('slot_id', $currentSlot->slot_id)
                ->where('access_decision', 'granted')
                ->whereDate('access_time', now())
                ->exists();

            if ($alreadyIn) {
                return $this->formatSuccessResponse(
                    $member->first_name,
                    $member->last_name,
                    "Déjà enregistré (Pas de doublon)",
                    $activeSub->plan_name,
                    $member->photo_url,
                    $activeSub->end_date
                );
            }

            if (!empty($allowedSlotIds) && !in_array($currentSlot->slot_id, $allowedSlotIds)) {
                $this->logAccess($member->member_id, $badgeUid, 'denied', 'Créneau non autorisé', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id);
                return $this->formatErrorResponse($member, "Créneau non inclus.");
            }
        }

        // 7. Grant Access
        $this->logAccess($member->member_id, $badgeUid, 'granted', null, $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id ?? null);

        return $this->formatSuccessResponse(
            $member->first_name,
            $member->last_name,
            "Bienvenue, " . $member->first_name,
            $activeSub->plan_name,
            $member->photo_url,
            $activeSub->end_date
        );
    }

    /**
     * Log access in database
     */
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

        // 🚀 Broadcast to Reception
        try {
            $member = $memberId ? Member::find($memberId) : null;
            $staff = $staffId ? Staff::find($staffId) : null;
            
            $name = 'Inconnu';
            $type = 'Inconnu';
            $photo = null;

            if ($member) {
                $name = $member->first_name . ' ' . $member->last_name;
                $type = 'Membre';
                $photo = $member->photo_url;
            } elseif ($staff) {
                $name = $staff->first_name . ' ' . $staff->last_name;
                $type = 'Staff';
                $photo = $staff->photo_url;
            }

            \App\Events\BadgeScanned::dispatch([
                'badge_uid' => $badgeUid,
                'decision' => $decision, // 'granted' or 'denied'
                'reason' => $reason,
                'person' => [
                    'name' => $name,
                    'type' => $type,
                    'photo' => $photo
                ],
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Throwable $e) {
            Log::error("Broadcast Error: " . $e->getMessage());
        }
    }

    /**
     * Format response to match Client Expectation (ApiResponse<Member>)
     */
    private function formatSuccessResponse($firstName, $lastName, $message, $planName, $photoUrl, $expiryDate = null)
    {
        // Client expects { data: { firstName, lastName, planName, expiryDate, photoUrl } }
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'firstName' => $firstName,
                'lastName'  => $lastName,
                'planName'  => $planName,
                'photoUrl'  => $photoUrl,
                'expiryDate' => $expiryDate ? Carbon::parse($expiryDate)->toDateString() : 'Active'
            ]
        ]);
    }

    /**
     * Format error response with Member data
     */
    private function formatErrorResponse(Member $member, $message)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => [
                'firstName' => $member->first_name,
                'lastName'  => $member->last_name,
                'planName'  => 'Accès Refusé',
                'photoUrl'  => $member->photo_url,
                'expiryDate' => null
            ]
        ], 403);
    }

    // Unused methods maintained for compatibility if needed, or can be removed.
    // ...
}
