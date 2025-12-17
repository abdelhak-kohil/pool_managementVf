<?php

namespace App\Modules\CRM\Actions;

use App\Models\Member\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMemberAction
{
    public function execute(int $memberId): void
    {
        DB::transaction(function () use ($memberId) {
            $member = Member::with(['subscriptions', 'accessBadge'])->findOrFail($memberId);

            // 1. Check Subscriptions
            if ($member->subscriptions()->count() > 0) {
                throw ValidationException::withMessages(['member' => 'Impossible de supprimer ce membre car il possède des abonnements.']);
            }

            // 2. Check Access History
            // Assuming accessLogs relationship exists or querying table directly
            $hasLogs = DB::table('pool_schema.access_logs')
                ->where('member_id', $memberId)
                ->exists();

            if ($hasLogs) {
                 throw ValidationException::withMessages(['member' => 'Impossible de supprimer ce membre car il possède un historique d’accès.']);
            }

            // 3. Unassign Badge (Physical Asset)
            if ($member->accessBadge) {
                $member->accessBadge->update([
                    'member_id' => null,
                    'status' => 'inactive',
                    'updated_at' => now(), // Assuming timestamps exists or safe to ignore if not
                ]);
            }

            // 4. Delete Member
            $member->delete();
        });
    }
}
