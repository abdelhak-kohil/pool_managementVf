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
