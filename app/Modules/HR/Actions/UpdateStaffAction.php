<?php

namespace App\Modules\HR\Actions;

use App\Modules\HR\DTOs\StaffData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateStaffAction
{
    public function execute(int $staffId, StaffData $data, ?string $badgeUid): int
    {
        return DB::transaction(function () use ($staffId, $data, $badgeUid) {
            $staff = DB::table('pool_schema.staff')->where('staff_id', $staffId)->first();
            if (!$staff) throw new \Exception("Personnel introuvable.");

            // 1. Prepare Update Array
            $updateData = [
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'username' => $data->username,
                'role_id' => $data->role_id,
                'is_active' => $data->is_active,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'specialty' => $data->specialty,
                'hiring_date' => $data->hiring_date,
                'salary_type' => $data->salary_type ?? 'per_hour',
                'hourly_rate' => $data->hourly_rate ?? 0.0,
                'notes' => $data->notes,
                'updated_at' => now(),
            ];

            if ($data->password) {
                $updateData['password_hash'] = Hash::make($data->password);
            }

            DB::table('pool_schema.staff')->where('staff_id', $staffId)->update($updateData);

            // 2. Handle Badge Linkage
            // Case A: Badge UID provided
            if ($badgeUid) {
                // Check if already assigned to this staff
                $currentBadge = DB::table('pool_schema.access_badges')->where('staff_id', $staffId)->first();
                
                if (!$currentBadge || $currentBadge->badge_uid !== $badgeUid) {
                     // New badge requested. Validate it.
                     $newBadge = DB::table('pool_schema.access_badges')->where('badge_uid', $badgeUid)->first();
                     if (!$newBadge) {
                         throw ValidationException::withMessages(['badge_uid' => 'Badge introuvable.']);
                     }
                     if (!is_null($newBadge->member_id) || (!is_null($newBadge->staff_id) && $newBadge->staff_id != $staffId)) {
                         throw ValidationException::withMessages(['badge_uid' => 'Badge déjà assigné.']);
                     }

                     // Unassign old
                     if ($currentBadge) {
                         DB::table('pool_schema.access_badges')->where('badge_id', $currentBadge->badge_id)
                           ->update(['staff_id' => null, 'status' => 'inactive']);
                     }

                     // Assign new
                     DB::table('pool_schema.access_badges')->where('badge_id', $newBadge->badge_id)
                       ->update(['staff_id' => $staffId, 'status' => 'active', 'issued_at' => now()]);
                }
            } 
            // Case B: Badge UID NOT provided (Cleared?)
            // If the input is explicitly present but null/empty, we might want to unassign?
            // For now, if $badgeUid is passed as NULL but explicit, we assume "No change" or "Remove"? 
            // The logic: if form field is empty, it means 'no badge'.
            // Let's assume strict: If form sends null, we remove badge.
             elseif ($badgeUid === null) {
                 // Check if we should unassign existing?
                 // Wait, CreateAction only assigns if provided.
                 // UpdateAction needs intent. If we pass null, does it mean "keep existing" or "remove"?
                 // Usually in forms: if field is empty, we remove relation?
                 // Let's look at controller logic I wrote in Step 1:
                 // The DTO/Request will pass 'badge_uid' as null if empty.
                 // So if null, I should unassign current badge? 
                 // Yes, strict sync.
                 
                 DB::table('pool_schema.access_badges')
                    ->where('staff_id', $staffId)
                    ->update(['staff_id' => null, 'status' => 'inactive']);
            }

            return $staffId;
        });
    }
}
