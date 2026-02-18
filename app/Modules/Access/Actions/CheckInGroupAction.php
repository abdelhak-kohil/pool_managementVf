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
    protected $pricingCalculator;

    public function __construct(\App\Services\Pricing\PricingCalculator $pricingCalculator)
    {
        $this->pricingCalculator = $pricingCalculator;
    }

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

        // 2. Check Subscription & Linked Contract
        // Strict Mode: Check-in MUST valid against an ACTIVE Subscription
        $activeSubscription = $group->subscriptions()
            ->with('contract') // Eager load linked contract
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$activeSubscription) {
             $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Abonnement inactif');
             return AccessResult::denied('Abonnement groupe inactif ou expiré.', $group->name, 'Groupe');
        }

        $linkedContract = $activeSubscription->contract; // The contract created with this subscription

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
            ->with('slot.activity')
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

        // 5. Contract Validation (if Pack)
        if ($linkedContract && $linkedContract->contract_type === 'fixed_package') {
            if ($linkedContract->remaining_sessions < 1) {
                $this->logAttendance($group, $badge, $staff, $attendeeCount, 'denied', 'Forfait épuisé', null, $linkedContract->contract_id);
                return AccessResult::denied('Forfait prépayé épuisé (0 séances restantes).', $group->name, 'Groupe');
            }
        }
        
        // 6. Success & Decrement
        $this->logAttendance($group, $badge, $staff, $attendeeCount, 'granted', null, $matchedSlot->slot_id, $linkedContract->contract_id ?? null);

        if ($linkedContract && $linkedContract->contract_type === 'fixed_package') {
            $linkedContract->decrement('remaining_sessions');
        }

        // Message Construction
        $priceMsg = "Inclus";
        if ($linkedContract) {
            if ($linkedContract->contract_type === 'fixed_package') {
                 $remainingDisplay = $linkedContract->remaining_sessions; // DB updated by decrement
                 $priceMsg = "Forfait Prépayé ({$remainingDisplay} restants)";
            } elseif ($linkedContract->contract_type === 'discount' || $linkedContract->contract_type === 'per_head') {
                 // Calculate estimated cost for awareness, even if post-paid
                 // Use calculator just for display value?
                $priceInfo = $this->pricingCalculator->calculateSessionPrice($group, $matchedSlot->slot->activity, $attendeeCount);
                $priceMsg = number_format($priceInfo['final_price'], 2) . " DZD";
            } else {
                // Flat fee, etc.
                $priceMsg = "Inclus (Abonnement)";
            }
        }

        return AccessResult::granted(
            "Accès autorisé. {$priceMsg}",
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

    private function logAttendance(PartnerGroup $group, AccessBadge $badge, Staff $staff, int $count, string $decision, ?string $reason = null, ?int $slotId = null, ?int $contractId = null)
    {
        PartnerGroupAttendance::create([
            'partner_group_id' => $group->group_id,
            'badge_id' => $badge->badge_id,
            'slot_id' => $slotId,
            'staff_id' => $staff->staff_id,
            'attendee_count' => $count,
            'access_time' => now(),
            'access_decision' => $decision,
            'denial_reason' => $reason,
            'contract_id' => $contractId // Log the contract used
        ]);

        // Broadcast Final Result for UI (omitted for brevity, same as before)
         \App\Events\BadgeScanned::dispatch([
            'badge_uid' => $badge->badge_uid,
            'decision' => $decision,
            'reason' => $reason ?? 'Accès Groupe Autorisé',
            'person' => [
                'name' => $group->name,
                'type' => 'Groupe',
                'photo' => null,
                'remaining_sessions' => null 
            ],
            'remaining_sessions' => null
        ]);
    }
}
