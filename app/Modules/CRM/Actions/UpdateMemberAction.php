<?php

namespace App\Modules\CRM\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Modules\CRM\DTOs\MemberData;

class UpdateMemberAction
{
    public function execute(int $memberId, MemberData $payload, ?int $badgeId, array $subscriptionStatuses, ?int $staffId)
    {
        return DB::transaction(function () use ($memberId, $payload, $badgeId, $subscriptionStatuses, $staffId) {
            $member = DB::table('pool_schema.members')->where('member_id', $memberId)->first();
            if (!$member) throw new \Exception("Membre introuvable.");

            // 1. Update Profile
            $updateData = [
                'first_name'    => $payload->first_name,
                'last_name'     => $payload->last_name,
                'email'         => $payload->email,
                'phone_number'  => $payload->phone_number,
                'address'       => $payload->address,
                'date_of_birth' => $payload->date_of_birth,
                'emergency_contact_name' => $payload->emergency_contact_name,
                'emergency_contact_phone' => $payload->emergency_contact_phone,
                'notes'         => $payload->notes,
                'health_conditions' => $payload->health_conditions,
                'updated_by'    => $staffId,
                'updated_at'    => now(), // Consistent usage
            ];

            if ($payload->photo && $payload->photo instanceof \Illuminate\Http\UploadedFile) {
                if ($member->photo_path) {
                    Storage::disk('public')->delete($member->photo_path);
                }
                $updateData['photo_path'] = $payload->photo->store('members/photos', 'public');
            }

            DB::table('pool_schema.members')->where('member_id', $memberId)->update($updateData);

            // 2. Handle Badge Change
            if ($badgeId) {
                $currentBadge = DB::table('pool_schema.access_badges')->where('member_id', $memberId)->first();
                
                if (!$currentBadge || $currentBadge->badge_id != $badgeId) {
                    // Unassign old
                    if ($currentBadge) {
                        DB::table('pool_schema.access_badges')
                            ->where('badge_id', $currentBadge->badge_id)
                            ->update(['member_id' => null, 'status' => 'inactive']);
                    }

                    // Assign new
                     $newBadge = DB::table('pool_schema.access_badges')->where('badge_id', $badgeId)->first();
                     // Check consistency
                     if ($newBadge && (!is_null($newBadge->member_id) && $newBadge->member_id != $memberId)) {
                         throw ValidationException::withMessages(['badge_id' => 'Le badge sélectionné est déjà attribué.']);
                     }
                     
                     DB::table('pool_schema.access_badges')
                        ->where('badge_id', $badgeId)
                        ->update(['member_id' => $memberId, 'status' => 'active', 'issued_at' => now()]);
                }
            }

            // 3. Update Subscription Statuses
            foreach ($subscriptionStatuses as $subId => $subData) {
                 DB::table('pool_schema.subscriptions')
                    ->where('subscription_id', $subId)
                    ->where('member_id', $memberId)
                    ->update([
                        'status' => $subData['status'],
                        'updated_by' => $staffId,
                        'updated_at' => now()
                    ]);
            }

            // 4. Integrity Check: Multiple Active Monthly Plans
             $activeWeeklyCount = DB::table('pool_schema.subscriptions as s')
                ->join('pool_schema.plans as p', 'p.plan_id', '=', 's.plan_id')
                ->where('s.member_id', $memberId)
                ->where('s.status', 'active')
                ->where('p.plan_type', 'monthly_weekly')
                ->count();

            if ($activeWeeklyCount > 1) {
                 throw ValidationException::withMessages(['subscriptions' => "Impossible : un membre ne peut pas avoir plus d’un abonnement actif de type 'mensuel/hebdomadaire'."]);
            }

            return $memberId;
        });
    }
}
