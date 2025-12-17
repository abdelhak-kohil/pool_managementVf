<?php

namespace App\Modules\HR\Actions;

use App\Modules\HR\DTOs\StaffData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateStaffAction
{
    public function execute(StaffData $data, ?string $badgeUid): int
    {
        return DB::transaction(function () use ($data, $badgeUid) {
            
            // 1. Create Staff
            $staffId = DB::table('pool_schema.staff')->insertGetId([
                'first_name' => $data->first_name,
                'last_name' => $data->last_name,
                'username' => $data->username,
                'password_hash' => Hash::make($data->password), 
                
                'role_id' => $data->role_id,
                'is_active' => $data->is_active,
                'email' => $data->email,
                'phone_number' => $data->phone_number,
                'specialty' => $data->specialty,
                'hiring_date' => $data->hiring_date,
                // Apply DB defaults if null to avoid NOT NULL constraint violation
                'salary_type' => $data->salary_type ?? 'per_hour',
                'hourly_rate' => $data->hourly_rate ?? 0.0,
                'notes' => $data->notes,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'staff_id');

            // 2. Assign Badge (Strict inventory mode)
            if ($badgeUid) {
                $badge = DB::table('pool_schema.access_badges')->where('badge_uid', $badgeUid)->first();
                
                if (!$badge) {
                    throw ValidationException::withMessages(['badge_uid' => 'Badge introuvable.']);
                }

                if (!is_null($badge->member_id) || (!is_null($badge->staff_id) && $badge->staff_id != $staffId)) {
                     throw ValidationException::withMessages(['badge_uid' => 'Ce badge est déjà assigné à quelqu un d autre.']);
                }

                DB::table('pool_schema.access_badges')
                    ->where('badge_id', $badge->badge_id)
                    ->update([
                        'staff_id' => $staffId,
                        'status' => 'active', // Mark active upon assignment
                        'issued_at' => now(),
                    ]);
            }

            return $staffId;
        });
    }
}
