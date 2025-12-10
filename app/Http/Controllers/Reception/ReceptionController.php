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
        $members = Member::with(['subscriptions.plan', 'subscriptions.weekdays', 'accessbadge'])
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
    public function checkIn(Member $member)
    {
        try {
            // 🔹 Get badge safely or use MANUAL
            $badgeUid = optional($member->accessbadge)->badge_uid ?? 'MANUAL';

            // ✅ Check for active subscription
            $activeSub = DB::table('pool_schema.subscriptions as s')
                ->join('pool_schema.plans as p', 'p.plan_id', '=', 's.plan_id')
                ->join('pool_schema.activities as a', 'a.activity_id', '=', 's.activity_id')
                ->where('s.member_id', $member->member_id)
                ->where('s.status', 'active')
                ->whereDate('s.start_date', '<=', now())
                ->whereDate('s.end_date', '>=', now())
                ->select('s.*', 'p.plan_type', 'a.activity_id')
                ->first();
            
            Log::error('badgeUid: ' . "----------------------------------");
   

            if (!$activeSub) {
                $this->logAccess($member->member_id, $badgeUid, 'denied', 'Aucun abonnement actif ou valide');
                return response()->json(['success' => false, 'message' => "{$member->first_name} {$member->last_name} n’a pas d’abonnement actif."]);
            }

            // 🇫🇷 Convert today's name to French (Robust Mapping)
            // We rely on the fact that PHP's 'l' format returns English day names.
            // We map them to the French names stored in the DB.
            $dayMap = [
                'Monday'    => 'Lundi',
                'Tuesday'   => 'Mardi',
                'Wednesday' => 'Mercredi',
                'Thursday'  => 'Jeudi',
                'Friday'    => 'Vendredi',
                'Saturday'  => 'Samedi',
                'Sunday'    => 'Dimanche',
            ];
            
            $todayEnglish = now()->format('l');
            $todayFr = $dayMap[$todayEnglish] ?? $todayEnglish;

            $todayId = DB::table('pool_schema.weekdays')
                ->where('day_name', $todayFr)
                ->value('weekday_id');

            if (!$todayId) {
                // Fallback or error if DB configuration is different
                Log::error("Day name '$todayFr' not found in weekdays table.");
                return response()->json(['success' => false, 'message' => "Erreur de configuration : Jour '$todayFr' introuvable."]);
            }

            // 🕒 Identify Current Time Slot
            $currentTime = now()->format('H:i:s');
            $currentSlot = DB::table('pool_schema.time_slots')
                ->where('end_time', '>=', $currentTime)
                ->where('start_time', '<=', $currentTime)
                ->where('weekday_id', $todayId)
                ->first();
            // ⛔ Restrict "Entretien" Activity
            if ($currentSlot) {
                $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
                if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                    $this->logAccess($member->member_id, $badgeUid, 'denied', 'Activité réservée à la maintenance');
                    return response()->json(['success' => false, 'message' => "Accès refusé : Activité 'Entretien' en cours."]);
                }
            }

            // ⚠️ Check Capacity Limit
            if ($currentSlot && $currentSlot->capacity) {
                // Count attendees in this slot today
                $attendeesCount = DB::table('pool_schema.access_logs')
                    ->where('access_decision', 'granted')
                    ->whereDate('access_time', now())
                    ->whereTime('access_time', '>=', $currentSlot->start_time)
                    ->whereTime('access_time', '<=', $currentSlot->end_time)
                    ->count();

                if ($attendeesCount >= $currentSlot->capacity) {
                    $this->logAccess(
                        $member->member_id, 
                        $badgeUid, 
                        'denied', 
                        'Capacité du créneau atteinte',
                        $activeSub->activity_id,
                        $activeSub->subscription_id,
                        $currentSlot->slot_id
                    );
                    return response()->json([
                        'success' => false, 
                        'message' => "Accès refusé : Le créneau est complet ({$currentSlot->capacity} personnes max)."
                    ]);
                }
            }

            // 🔹 Restrict only for "monthly_weekly" plans
            if ($activeSub->plan_type === 'monthly_weekly') {
                $todayRecord = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->first();

                if ($todayRecord) {
                    $allowedDays = DB::table('pool_schema.subscription_allowed_days')
                        ->where('subscription_id', $activeSub->subscription_id)
                        ->pluck('weekday_id')
                        ->toArray();

                    if (!in_array($todayRecord->weekday_id, $allowedDays)) {
                        $this->logAccess(
                            $member->member_id, 
                            $badgeUid, 
                            'denied', 
                            'Jour non autorisé',
                            $activeSub->activity_id ?? null,
                            $activeSub->subscription_id,
                            $currentSlot->slot_id ?? null
                        );
                        return response()->json([
                            'success' => false,
                            'message' => "Accès refusé : Aujourd’hui ({$todayFr}) n’est pas un jour autorisé."
                        ]);
                    }
                }
            }

            // 🔍 Check if subscription is restricted to specific slots
            if ($currentSlot) {
                $allowedSlotIds = DB::table('pool_schema.subscription_slots')
                    ->where('subscription_id', $activeSub->subscription_id)
                    ->pluck('slot_id')
                    ->toArray();
                
                
                Log::error('currentSlot: ' . $currentSlot->slot_id);
                
                // 🛑 Check if already checked in for this slot
                $alreadyIn = DB::table('pool_schema.access_logs')
                    ->where('member_id', $member->member_id)
                    ->where('slot_id', $currentSlot->slot_id)
                    ->where('access_decision', 'granted')
                    ->whereDate('access_time', now())
                    ->exists();

                if ($alreadyIn) {
                    return response()->json([
                        'success' => true,
                        'message' => "{$member->first_name} est déjà enregistré pour ce créneau (Pas de doublon).",
                    ]);
                }

                if (!empty($allowedSlotIds) && !in_array($currentSlot->slot_id, $allowedSlotIds)) {
                    $this->logAccess(
                        $member->member_id, 
                        $badgeUid, 
                        'denied', 
                        'Créneau non autorisé',
                        $activeSub->activity_id ?? null,
                        $activeSub->subscription_id,
                        $currentSlot->slot_id
                    );
                    return response()->json([
                        'success' => false,
                        'message' => "Accès refusé : Ce créneau n'est pas inclus dans votre abonnement."
                    ]);
                }
            }

            // ✅ Grant access
            $this->logAccess(
                $member->member_id, 
                $badgeUid, 
                'granted', 
                null,
                $activeSub->activity_id ?? null,
                $activeSub->subscription_id,
                $currentSlot->slot_id ?? null
            );

            return response()->json([
                'success' => true,
                'message' => "{$member->first_name} {$member->last_name} a bien effectué son check-in.",
            ]);

        } catch (\Throwable $e) {
            Log::error('Échec du check-in : '.$e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erreur serveur : '.$e->getMessage()], 500);
        }
    }

    /**
     * Check-in by Badge UID (for NFC Scan).
     */
    public function checkInByBadge(Request $request)
    {
        $request->validate(['badge_uid' => 'required|string']);
        
        $badge = AccessBadge::where('badge_uid', $request->badge_uid)->first();
        
        if (!$badge) {
            return response()->json(['success' => false, 'message' => 'Badge inconnu.']);
        }
        
            // 1. Check if it's a Staff Badge (Coach/Maintenance)
            if ($badge->staff_id) {
                $staff = $badge->staff;

                // Check for "Entretien" activity
                // 🇫🇷 Convert today's name to French
                $dayMap = [
                    'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
                    'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche',
                ];
                $todayFr = $dayMap[now()->format('l')] ?? now()->format('l');
                $todayId = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->value('weekday_id');
                $currentTime = now()->format('H:i:s');

                $currentSlot = DB::table('pool_schema.time_slots')
                    ->where('weekday_id', $todayId)
                    ->where('start_time', '<=', $currentTime)
                    ->where('end_time', '>=', $currentTime)
                    ->first();

                if ($currentSlot) {
                    $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
                    
                    if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                        // Check if staff is maintenance
                        $isMaintenance = false;
                        if ($staff->role && strtolower($staff->role->role_name) === 'maintenance') {
                            $isMaintenance = true;
                        }
                        if (strtolower($staff->username) === 'maintenance') {
                            $isMaintenance = true;
                        }

                        if (!$isMaintenance) {
                            $this->logAccess(null, $badge->badge_uid, 'denied', 'Accès réservé maintenance', null, null, $currentSlot->slot_id, $staff->staff_id);
                            return response()->json(['success' => false, 'message' => "Accès refusé : Seul le personnel de maintenance peut accéder."]);
                        }
                    }

                    // 🛑 Prevent Duplicate Check-in for Staff
                    $alreadyIn = DB::table('pool_schema.access_logs')
                        ->where('staff_id', $staff->staff_id)
                        ->where('slot_id', $currentSlot->slot_id)
                        ->where('access_decision', 'granted')
                        ->whereDate('access_time', now())
                        ->exists();

                    if ($alreadyIn) {
                        return response()->json([
                            'success' => true,
                            'message' => "{$staff->first_name} est déjà noté présent pour ce créneau.",
                        ]);
                    }
                }
                
                // Log attendance
                $this->logAccess(
                    null, 
                    $badge->badge_uid, 
                    'granted', 
                    'Staff Check-in', 
                    null, 
                    null, 
                    $currentSlot->slot_id ?? null,
                    $staff->staff_id
                );
    
                return response()->json([
                    'success' => true, 
                    'message' => "Bienvenue {$staff->first_name} !"
                ]);
            }

        // 2. Check if it's a Member Badge
        if (!$badge->member_id) {
            return response()->json(['success' => false, 'message' => 'Ce badge n\'est assigné à personne.']);
        }
        
        $member = Member::find($badge->member_id);
        
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Membre introuvable.']);
        }
        
        return $this->checkIn($member);
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
