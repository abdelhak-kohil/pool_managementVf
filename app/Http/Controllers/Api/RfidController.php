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
    public function checkInMember(Request $request)
    {
        $request->validate([
            'badge_uid' => 'required|string',
            'reader_id' => 'nullable|string',
        ]);

        $badgeUid = $request->input('badge_uid');
        $readerId = $request->input('reader_id', 'API');

        try {
            // 1. Find Badge
            $badge = AccessBadge::where('badge_uid', $badgeUid)->first();

            if (!$badge) {
                 return response()->json([
                    'success' => false,
                    'message' => 'Badge inconnu.',
                    'code' => 404
                ], 404);
            }

            // ---------------------------------------------------------
            // A) STAFF CHECK-IN LOGIC
            // ---------------------------------------------------------
            if ($badge->staff_id) {
                return $this->processStaffCheckIn($badge, $readerId);
            }

            // ---------------------------------------------------------
            // B) MEMBER CHECK-IN LOGIC
            // ---------------------------------------------------------
            if (!$badge->member_id) {
                return response()->json(['success' => false, 'message' => 'Badge non assigné.'], 404);
            }

            $member = Member::with(['subscriptions.plan', 'subscriptions.weekdays'])->find($badge->member_id);
            if (!$member) {
                return response()->json(['success' => false, 'message' => 'Membre introuvable.'], 404);
            }

            return $this->processMemberCheckIn($member, $badge->badge_uid, $readerId);

        } catch (\Throwable $e) {
            Log::error("RFID Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Process Staff Check-In
     */
    private function processStaffCheckIn($badge, $readerId)
    {
        $staff = $badge->staff;
        if (!$staff) return response()->json(['success' => false, 'message' => 'Staff introuvable.'], 404);

        // Date/Time setup
        $dayMap = [
            'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche',
        ];
        $todayFr = $dayMap[now()->format('l')] ?? now()->format('l');
        $todayId = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->value('weekday_id');
        $currentTime = now()->format('H:i:s');

        // Identify Slot
        $currentSlot = DB::table('pool_schema.time_slots')
            ->where('weekday_id', $todayId)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->first();

        if ($currentSlot) {
            $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
            
            // Maintenance Restriction
            if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                $isMaintenance = false;
                if ($staff->role && strtolower($staff->role->role_name) === 'maintenance') $isMaintenance = true;
                if (strtolower($staff->username) === 'maintenance') $isMaintenance = true;

                if (!$isMaintenance) {
                    $this->logAccess(null, $badge->badge_uid, 'denied', 'Accès réservé maintenance', null, null, $currentSlot->slot_id, $staff->staff_id);
                    return response()->json(['success' => false, 'message' => "Accès réservé à la maintenance."], 403);
                }
            }

            // Duplicate Check
            $alreadyIn = DB::table('pool_schema.access_logs')
                ->where('staff_id', $staff->staff_id)
                ->where('slot_id', $currentSlot->slot_id)
                ->where('access_decision', 'granted')
                ->whereDate('access_time', now())
                ->exists();

            if ($alreadyIn) {
                // Return success but indicate already entered
                 return $this->formatSuccessResponse(
                    $staff->first_name,
                    $staff->last_name,
                    "Déjà présent (Staff)",
                    "Staff",
                    null
                );
            }
        }

        // Grant Access
        $this->logAccess(null, $badge->badge_uid, 'granted', 'Staff Check-in', null, null, $currentSlot->slot_id ?? null, $staff->staff_id);

        return $this->formatSuccessResponse(
            $staff->first_name,
            $staff->last_name,
            "Bienvenue Staff",
            "Personnel",
            null
        );
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
