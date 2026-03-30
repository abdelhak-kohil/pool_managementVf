<?php

namespace App\Modules\Access\Actions;

use App\Models\Member\Member;
use App\Modules\Access\DTOs\AccessResult;
use Illuminate\Support\Facades\DB;

class CheckInMemberAction
{
    public function __construct(
        protected LogAccessAction $logger
    ) {}

    public function execute(Member $member, string $badgeUid): AccessResult
    {
        // 1. Check Active Subscription
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
            $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Aucun abonnement actif ou valide', null, null, null);
            return AccessResult::denied("Aucun abonnement actif.", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id);
        }

        // 2. Identify Time/Slot
        $dayMap = [
            'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi', 'Sunday' => 'Dimanche',
        ];
        $todayFr = $dayMap[now()->format('l')] ?? now()->format('l');
        $todayId = DB::table('pool_schema.weekdays')->where('day_name', $todayFr)->value('weekday_id');
        
        if (!$todayId) {
             // Config error
             return AccessResult::denied("Erreur configuration jour.", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id);
        }

        $currentTime = now()->format('H:i:s');
        $currentSlot = DB::table('pool_schema.time_slots')
            ->where('end_time', '>=', $currentTime)
            ->where('start_time', '<=', $currentTime)
            ->where('weekday_id', $todayId)
            ->first();

        // 3. Maintenance Check
        if ($currentSlot) {
            $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
            if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                 $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Activité réservée à la maintenance', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id);
                 return AccessResult::denied("Activité 'Entretien' en cours.", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id);
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
                // Check if USER is already inside? (Duplicate check usually handles this so we don't double count if they rescan)
                // However, simple capacity check counts ALL granted logs.
                // Assuming capacity is strictly enforcing headcount.
                
                // Let's run Duplicate Check Early? 
                // If I am already inside, I shouldn't be blocked by capacity (I am part of the capacity).
                
                $alreadyIn = DB::table('pool_schema.access_logs')
                    ->where('member_id', $member->member_id)
                    ->where('slot_id', $currentSlot->slot_id)
                    ->where('access_decision', 'granted')
                    ->whereDate('access_time', now())
                    ->exists();

                if (!$alreadyIn) {
                    $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Capacité du créneau atteinte', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id);
                    return AccessResult::denied("Le créneau est complet.", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id);
                }
            }
        }

        // 5. Plan Days Restriction (Monthly/Weekly)
        $remainingSessions = null;
        if ($activeSub->plan_type === 'monthly_weekly') {
             $allowedDays = DB::table('pool_schema.subscription_allowed_days')
                ->where('subscription_id', $activeSub->subscription_id)
                ->pluck('weekday_id')
                ->toArray();

             if (!in_array($todayId, $allowedDays)) {
                 $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Jour non inclus dans l\'abonnement', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot ? $currentSlot->slot_id : null);
                 return AccessResult::denied("Accès non autorisé ce jour (Plan).", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id);
             }

             // Check Visits Per Week Limit
             if ($activeSub->visits_per_week) {
                 $startOfWeek = now()->startOfWeek();
                 $endOfWeek = now()->endOfWeek();

                 $visitsThisWeek = DB::table('pool_schema.access_logs')
                    ->where('subscription_id', $activeSub->subscription_id)
                    ->where('access_decision', 'granted')
                    ->whereBetween('access_time', [$startOfWeek, $endOfWeek])
                    ->count();

                 if ($visitsThisWeek >= $activeSub->visits_per_week) {
                     $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Limite de visites hebdomadaires atteinte', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot ? $currentSlot->slot_id : null);
                     return AccessResult::denied("Limite scans atteinte ({$activeSub->visits_per_week}/semaine).", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id, null, 0);
                 }
                 
                 // Valid scan: Reduce by 1 (virtually, since we are about to log this scan)
                 // OR display remaining BEFORE this scan? Convention: usually displays remaining INCLUDING this one if successful, or AFTER.
                 // Let's display remaining AFTER this scan.
                 // Example: Limit 2. 0 used. Scan now. Remaining = 2 - 1 = 1.
                 $remainingSessions = max(0, $activeSub->visits_per_week - ($visitsThisWeek + 1));
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
                 // Recalculate remaining correctly if duplicated scan (don't subtract again)
                 // If already in, visitsThisWeek ALREADY includes today. 
                 // So remaining = Limit - visitsThisWeek.
                 if (isset($activeSub->visits_per_week)) {
                      $visitsThisWeek = DB::table('pool_schema.access_logs')
                        ->where('subscription_id', $activeSub->subscription_id)
                        ->where('access_decision', 'granted')
                        ->whereBetween('access_time', [now()->startOfWeek(), now()->endOfWeek()])
                        ->count();
                      $remainingSessions = max(0, $activeSub->visits_per_week - $visitsThisWeek);
                 }

                return AccessResult::granted(
                    "Déjà enregistré (Pas de doublon)", 
                    $member->first_name . ' ' . $member->last_name, 
                    'Member', 
                    $member->photo_url,
                    $activeSub->plan_name,
                    $activeSub->end_date,
                    $member->member_id,
                    null,
                    $remainingSessions
                );
            }

            if (!empty($allowedSlotIds) && !in_array($currentSlot->slot_id, $allowedSlotIds)) {
                $this->logger->execute($member->member_id, null, $badgeUid, 'denied', 'Créneau non autorisé', $activeSub->activity_id, $activeSub->subscription_id, $currentSlot->slot_id);
                return AccessResult::denied("Créneau non inclus.", $member->first_name . ' ' . $member->last_name, 'Member', $member->photo_url, $member->member_id, null, $remainingSessions);
            }
        }

        // 7. Grant Access
        $this->logger->execute($member->member_id, null, $badgeUid, 'granted', null, $activeSub->activity_id, $activeSub->subscription_id, $currentSlot ? $currentSlot->slot_id : null);

        return AccessResult::granted(
            "Bienvenue, " . $member->first_name,
            $member->first_name . ' ' . $member->last_name,
            'Member',
            $member->photo_url,
            $activeSub->plan_name,
            $activeSub->end_date,
            $member->member_id,
            null,
            $remainingSessions
        );
    }
}
