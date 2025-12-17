<?php

namespace App\Modules\Access\Actions;

use App\Models\Staff\Staff;
use App\Modules\Access\DTOs\AccessResult;
use Illuminate\Support\Facades\DB;

class CheckInStaffAction
{
    public function __construct(
        protected LogAccessAction $logger
    ) {}

    public function execute(Staff $staff, string $badgeUid): AccessResult
    {
        // 1. Determine Time/Slot
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

        // 2. Maintanance / Slot Activity Check
        if ($currentSlot) {
            $slotActivity = DB::table('pool_schema.activities')->where('activity_id', $currentSlot->activity_id)->first();
            
            if ($slotActivity && strtolower($slotActivity->name) === 'entretien') {
                $isMaintenance = false;
                // Check Role
                if ($staff->role_id) {
                     $roleName = DB::table('pool_schema.roles')->where('role_id', $staff->role_id)->value('role_name');
                     if ($roleName && strtolower($roleName) === 'maintenance') {
                         $isMaintenance = true;
                     }
                }
                // Check Username convention (legacy?)
                if (strtolower($staff->username) === 'maintenance') {
                    $isMaintenance = true;
                }

                if (!$isMaintenance) {
                    $this->logger->execute(null, $staff->staff_id, $badgeUid, 'denied', 'Accès réservé maintenance', null, null, $currentSlot->slot_id);
                    return AccessResult::denied("Accès réservé à la maintenance.", $staff->first_name . ' ' . $staff->last_name, 'Staff', $staff->photo_url, null, $staff->staff_id);
                }
            }

            // 3. Duplicate Check
            $alreadyIn = DB::table('pool_schema.access_logs')
                ->where('staff_id', $staff->staff_id)
                ->where('slot_id', $currentSlot->slot_id)
                ->where('access_decision', 'granted')
                ->whereDate('access_time', now())
                ->exists();

            if ($alreadyIn) {
                 return AccessResult::granted(
                    "Déjà présent (Staff)",
                    $staff->first_name . ' ' . $staff->last_name,
                    'Staff',
                    $staff->photo_url,
                    null, null, null, $staff->staff_id
                );
            }
        }

        // 4. Grant
        $this->logger->execute(null, $staff->staff_id, $badgeUid, 'granted', 'Staff Check-in', null, null, $currentSlot->slot_id ?? null);

        return AccessResult::granted(
            "Bienvenue Staff",
            $staff->first_name . ' ' . $staff->last_name,
            'Staff',
            $staff->photo_url,
            null, null, null, $staff->staff_id
        );
    }
}
