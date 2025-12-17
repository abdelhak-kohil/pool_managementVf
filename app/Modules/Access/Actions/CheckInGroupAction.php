<?php

namespace App\Modules\Access\Actions;

use App\Models\Member\PartnerGroup;
use App\Models\Member\PartnerGroupAttendance;
use App\Models\Member\AccessBadge;
use App\Models\Staff\Staff;
use App\Modules\Access\DTOs\AccessResult;
use Carbon\Carbon;

class CheckInGroupAction
{
    public function execute(AccessBadge $badge, Staff $staff, int $attendeeCount): AccessResult
    {
        $group = $badge->partnerGroup;

        if (!$group) {
            return AccessResult::denied('Badge invalide (non lié à un groupe).');
        }

        // 1. Check Badge Status
        if ($badge->status !== 'active') {
             $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Badge inactif/révoqué');
             return AccessResult::denied('Ce badge est inactif ou révoqué.', $group->name, 'Groupe');
        }

        // 2. Check Subscription
        $activeSubscription = $group->subscriptions()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        if (!$activeSubscription) {
             $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Abonnement inactif');
             return AccessResult::denied('Abonnement groupe inactif ou expiré.', $group->name, 'Groupe');
        }

        // 3. Find Current Slot
        // We look for a PartnerGroupSlot that matches current time/day
        $now = now();
        $currentWeekdayId = $now->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
        $currentTime = $now->format('H:i:s');

        \Illuminate\Support\Facades\Log::info("Group Check-in Debug: Group={$group->name}, Day={$currentWeekdayId}, Time={$currentTime}");

        $matchedSlot = $group->slots()
            ->whereHas('slot', function ($query) use ($currentWeekdayId, $currentTime) {
                $query->where('weekday_id', $currentWeekdayId)
                      ->where('start_time', '<=', $currentTime)
                      ->where('end_time', '>=', $currentTime);
            })
            ->with('slot')
            ->first();

        if (!$matchedSlot) {
            \Illuminate\Support\Facades\Log::warning("Group Check-in Denied: No matching slot found for Group={$group->group_id} at Time={$currentTime} on Day={$currentWeekdayId}");
            $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Pas de créneau assigné');
            return AccessResult::denied('Aucun créneau réservé pour ce groupe actuellement.', $group->name, 'Groupe');
        }

        // 4. Check Cumulative Capacity
        // Sum attendees for this group in this slot TODAY (only granted accesses)
        $alreadyAttendedCount = PartnerGroupAttendance::where('partner_group_id', $group->group_id)
            ->where('slot_id', $matchedSlot->slot_id)
            ->where('access_decision', 'granted')
            ->whereDate('access_time', $now->toDateString())
            ->sum('attendee_count');

        $totalProjected = $alreadyAttendedCount + $attendeeCount;

        \Illuminate\Support\Facades\Log::info("Group Check-in Capacity: Used={$alreadyAttendedCount}, Request={$attendeeCount}, Max={$matchedSlot->max_capacity}");

        if ($totalProjected > $matchedSlot->max_capacity) {
             $remaining = max(0, $matchedSlot->max_capacity - $alreadyAttendedCount);
             $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Capacité dépassée');
             return AccessResult::denied("Capacité dépassée. Déjà entrés: {$alreadyAttendedCount}, Restants: {$remaining}. Demandés: {$attendeeCount}.", $group->name, 'Groupe');
        }

        // 4. Success
        $this->logAttendance($group, $badge, $staff, $attendeeCount, 'granted', null, $matchedSlot->slot_id);

        return AccessResult::granted(
            'Accès autorisé.',
            $group->name,
            'Groupe',
            null, // photo
            $matchedSlot->slot->activity->name ?? 'Activité Groupe',
            null, // expiry
            null, // memberId
            null, // staffId
            $matchedSlot->max_capacity - $attendeeCount
        );
    }

    private function logAttendance(PartnerGroup $group, AccessBadge $badge, Staff $staff, int $count, string $decision, ?string $reason = null, ?int $slotId = null)
    {
        PartnerGroupAttendance::create([
            'partner_group_id' => $group->group_id,
            'badge_id' => $badge->badge_id,
            'slot_id' => $slotId,
            'staff_id' => $staff->staff_id,
            'attendee_count' => $count,
            'access_time' => now(),
            'access_decision' => $decision,
            'denial_reason' => $reason
        ]);

        // Broadcast Final Result for UI
        \App\Events\BadgeScanned::dispatch([
            'badge_uid' => $badge->badge_uid,
            'decision' => $decision,
            'reason' => $reason ?? 'Accès Groupe Autorisé',
            'person' => [
                'name' => $group->name,
                'type' => 'Groupe',
                'photo' => null,
                'remaining_sessions' => null // Capacity logic could go here
            ],
            'remaining_sessions' => null
        ]);
    }
}
