<?php

namespace App\Modules\CRM\Actions;

use App\Modules\CRM\DTOs\MemberData;
use App\Modules\Sales\DTOs\SubscriptionData;
use App\Modules\Sales\Actions\CreateSubscriptionAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateMemberAction
{
    public function __construct(
        protected CreateSubscriptionAction $createSubscriptionAction
    ) {}

    public function execute(MemberData $memberData, SubscriptionData $subscriptionData, ?int $staffId): int
    {
        return DB::transaction(function () use ($memberData, $subscriptionData, $staffId) {
            // 1. Handle Photo Upload
            $photoPath = null;
            if ($memberData->photo && $memberData->photo instanceof \Illuminate\Http\UploadedFile) {
                $photoPath = $memberData->photo->store('members/photos', 'public');
            }

            // 2. Create Member Record
            $memberId = DB::table('pool_schema.members')->insertGetId([
                'first_name'   => $memberData->first_name,
                'last_name'    => $memberData->last_name,
                'phone_number' => $memberData->phone_number,
                'email'        => $memberData->email,
                'date_of_birth'=> $memberData->date_of_birth,
                'address'      => $memberData->address,
                'photo_path'   => $photoPath,
                'emergency_contact_name' => $memberData->emergency_contact_name,
                'emergency_contact_phone' => $memberData->emergency_contact_phone,
                'notes'        => $memberData->notes,
                'health_conditions' => $memberData->health_conditions,
                'created_by'   => $staffId,
                'updated_by'   => $staffId,
                'created_at'   => now(),
                'updated_at'   => now(), // Added updated_at for consistency
            ], 'member_id'); // Return ID from sequence

            // 3. Assign Badge
            if ($memberData->badge_uid) {
                $badge = DB::table('pool_schema.access_badges')->where('badge_uid', $memberData->badge_uid)->first();
                if ($badge) {
                    DB::table('pool_schema.access_badges')
                        ->where('badge_id', $badge->badge_id)
                        ->update([
                            'member_id' => $memberId,
                            'status'    => $memberData->badge_status,
                            'issued_at' => now(), // Good practice to timestamp issue
                        ]);
                }
            }

            // 4. Create Subscription (Delegation)
            // We need to inject the new member_id into the subscription data
            // Since DTOs are immutable-ish (readonly in php 8.2 usually, but here public props), we can clone or modify.
            // Our DTO properties are public, so we can just set it.
            $subscriptionData->member_id = $memberId;
            
            $this->createSubscriptionAction->execute($subscriptionData);

            return $memberId;
        });
    }
}
