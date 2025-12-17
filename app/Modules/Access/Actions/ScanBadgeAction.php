<?php

namespace App\Modules\Access\Actions;

use App\Models\Member\AccessBadge;
use App\Models\Member\Member;
use App\Models\Staff\Staff;
use App\Modules\Access\DTOs\AccessResult;
use App\Events\BadgeScanned;

class ScanBadgeAction
{
    public function __construct(
        protected CheckInMemberAction $checkInMember,
        protected CheckInStaffAction $checkInStaff,
        protected LogAccessAction $logger
    ) {}

    public function execute(string $badgeUid): AccessResult
    {
        $badge = AccessBadge::where('badge_uid', $badgeUid)->first();

        if (!$badge) {
            $this->logger->execute(null, null, $badgeUid, 'denied', 'Badge inconnu', null, null, null);
            return AccessResult::denied("Badge inconnu.", null, null, null);
        }

        // 1. Staff Check
        if ($badge->staff_id) {
            $staff = Staff::find($badge->staff_id);
            if (!$staff) {
                // Determine if we should log this as a system error or access denied?
                // Log as denied for now to notify reception
                $this->logger->execute(null, null, $badgeUid, 'denied', 'Staff introuvable (liaison invalide)', null, null, null);
                return AccessResult::denied("Staff introuvable (liaison invalide).");
            }
            
            // CheckInStaffAction handles logging/broadcasting internally
            return $this->checkInStaff->execute($staff, $badgeUid);
        }

        // 2. Member Check
        if ($badge->member_id) {
            $member = Member::find($badge->member_id);
            if (!$member) {
                $this->logger->execute(null, null, $badgeUid, 'denied', 'Membre introuvable (liaison invalide)', null, null, null);
                return AccessResult::denied("Membre introuvable (liaison invalide).");
            }

            // CheckInMemberAction handles logging/broadcasting internally
            return $this->checkInMember->execute($member, $badgeUid);
        }

        // 3. Partner Group Check
        if ($badge->partner_group_id) {
            $group = \App\Models\Member\PartnerGroup::find($badge->partner_group_id);
            if (!$group) {
                $this->logger->execute(null, null, $badgeUid, 'denied', 'Groupe introuvable (liaison invalide)', null, null, null);
                return AccessResult::denied("Groupe introuvable (liaison invalide).");
            }

            // 3.1 Check Badge Status
            if ($badge->status !== 'active') {
                 $this->broadcastAccess($badgeUid, 'denied', 'Badge inactif/révoqué', $group->name, 'Groupe');
                 return AccessResult::denied('Badge inactif/révoqué', $group->name, 'Groupe');
            }

            // 3.2 Check Subscription
            $activeSubscription = $group->subscriptions()
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->exists();

            if (!$activeSubscription) {
                 $this->broadcastAccess($badgeUid, 'denied', 'Abonnement groupe inactif ou expiré', $group->name, 'Groupe');
                 return AccessResult::denied('Abonnement groupe inactif ou expiré.', $group->name, 'Groupe');
            }

            // 3.3 Find Current Slot
            $now = now();
            $currentWeekdayId = $now->dayOfWeekIso;
            $currentTime = $now->format('H:i:s');

            $matchedSlot = $group->slots()
                ->whereHas('slot', function ($query) use ($currentWeekdayId, $currentTime) {
                    $query->where('weekday_id', $currentWeekdayId)
                          ->where('start_time', '<=', $currentTime)
                          ->where('end_time', '>=', $currentTime);
                })
                ->with('slot')
                ->first();

            if (!$matchedSlot) {
                $this->broadcastAccess($badgeUid, 'denied', 'Aucun créneau réservé actuellement', $group->name, 'Groupe');
                return AccessResult::denied('Aucun créneau réservé pour ce groupe actuellement.', $group->name, 'Groupe');
            }

            // Return 'group' type result.
            $resultPayload = [
                'type' => 'group',
                'group' => $group,
                'badge_uid' => $badgeUid,
                'badge_id' => $badge->badge_id
            ];
            
            // Broadcast for Web UI (still useful for Reception)
            \App\Events\BadgeScanned::dispatch([
                 'badge_uid' => $badgeUid,
                 'decision' => 'pending', 
                 'reason' => 'Groupe détecté',
                 'person' => [
                     'name' => $group->name,
                     'type' => 'Groupe',
                     'photo' => null,
                 ],
                 'action' => 'request_count',
                 'data' => $resultPayload
            ]);

            // Parameters: isGranted, message, personName, personType, planName, photoUrl, expiryDate...
            return new AccessResult(
                true, 
                "Badge Groupe détecté.", 
                $group->name,   // personName
                'Groupe',       // personType
                $matchedSlot->slot->activity->name ?? 'Activité Groupe', // planName
                null,           // photoUrl
                null,           // expiryDate
                null,           // memberId
                null,           // staffId
                // Return hypothetical remaining capacity assuming count=1 for display?
                $matchedSlot->max_capacity // remainingSessions (show max capacity or something)
            );
        }

        // Badge exists but no user assigned
        $this->logger->execute(null, null, $badgeUid, 'denied', 'Badge non assigné', null, null, null);
        return AccessResult::denied("Badge non assigné.");
    }
    private function broadcastAccess($badgeUid, $decision, $reason, $name, $type)
    {
        \App\Events\BadgeScanned::dispatch([
            'badge_uid' => $badgeUid,
            'decision' => $decision,
            'reason' => $reason,
            'person' => [
                'name' => $name,
                'type' => $type,
                'photo' => null,
            ]
        ]);
    }
}
