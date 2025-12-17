<?php

namespace App\Modules\Operations\Actions\Reservations;

use App\Modules\Operations\DTOs\ReservationData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateReservationAction
{
    public function execute(ReservationData $data): int
    {
        return DB::transaction(function () use ($data) {
            // 1. Fetch Slot
            $slot = DB::table('pool_schema.time_slots')
                ->where('slot_id', $data->slot_id)
                ->first();

            if (!$slot) {
                throw ValidationException::withMessages(['slot_id' => 'Créneau introuvable.']);
            }

            // 2. Business Rules: Check Blocked/Maintenance
            $activity = DB::table('pool_schema.activities')->where('activity_id', $slot->activity_id)->first();
            if ($activity && strtolower($activity->name) === 'entretien') {
                 throw ValidationException::withMessages(['slot_id' => 'Cette activité est réservée à la maintenance.']);
            }

            if ($slot->is_blocked || str_contains(strtolower($slot->notes ?? ''), 'entretien')) {
                 throw ValidationException::withMessages(['slot_id' => 'Ce créneau est bloqué ou réservé pour entretien.']);
            }

            // 3. Check Existing Reservation
            $existing = DB::table('pool_schema.reservations')
                ->where('slot_id', $slot->slot_id)
                ->where('status', 'confirmed')
                ->exists();

            if ($existing) {
                 throw ValidationException::withMessages(['slot_id' => 'Ce créneau est déjà réservé.']);
            }

            // 4. Create Reservation
            return DB::table('pool_schema.reservations')->insertGetId([
                'slot_id'           => $slot->slot_id,
                'member_id'         => $data->reservation_type === 'member_private' ? $data->member_id : null,
                'partner_group_id'  => $data->reservation_type === 'partner_group' ? $data->partner_group_id : null,
                'reservation_type'  => $data->reservation_type,
                'reserved_at'       => now(),
                'status'            => 'confirmed',
                'notes'             => $data->notes
            ], 'reservation_id');
        });
    }
}
