<?php

namespace App\Modules\Access\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Member\Member;
use App\Models\Staff\Staff;

class LogAccessAction
{
    public function execute(?int $memberId, ?int $staffId, string $badgeUid, string $decision, ?string $reason, ?int $activityId, ?int $subscriptionId, ?int $slotId, ?int $remainingSessions = null)
    {
        // 1. Insert DB Log
        DB::table('pool_schema.access_logs')->insert([
            'member_id'       => $memberId,
            'staff_id'        => $staffId,
            'badge_uid'       => $badgeUid,
            'access_decision' => $decision,
            'denial_reason'   => $reason ?? ($remainingSessions !== null ? "Séances: $remainingSessions" : null), // Optional: store in reason via trick? No, don't polute reason.
            'access_time'     => now(),
            'activity_id'     => $activityId,
            'subscription_id' => $subscriptionId,
            'slot_id'         => $slotId,
        ]);

        // 2. Broadcast Event
        try {
            $name = 'Inconnu';
            $type = 'Inconnu';
            $photo = null;

            if ($memberId) {
                // Use Eloquent to correctly resolve table names and accessors
                $mModel = Member::find($memberId);
                if ($mModel) {
                     $name = $mModel->first_name . ' ' . $mModel->last_name;
                     $type = 'Membre';
                     $photo = $mModel->photo_url; // Uses accessor or correct column
                }
            } elseif ($staffId) {
                $sModel = Staff::find($staffId);
                if ($sModel) {
                    $name = $sModel->first_name . ' ' . $sModel->last_name;
                    $type = 'Staff';
                    $photo = $sModel->photo_url;
                }
            }

            \App\Events\BadgeScanned::dispatch([
                'badge_uid' => $badgeUid,
                'decision' => $decision,
                'reason' => $reason,
                'person' => [
                    'name' => $name,
                    'type' => $type,
                    'photo' => $photo,
                    'remaining_sessions' => $remainingSessions // Adding here
                ],
                'remaining_sessions' => $remainingSessions, // Or detailed here
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Throwable $e) {
            Log::error("Broadcast Error (LogAccessAction): " . $e->getMessage());
        }
    }
}
