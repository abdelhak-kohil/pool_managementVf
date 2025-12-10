<?php

namespace App\Services;

use App\Models\Staff\Staff;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccessControlService
{
    /**
     * Verify badge access for a specific action and location.
     * 
     * @param string $badgeUid
     * @param string $location
     * @param string $actionType (entry, exit, door_open, maintenance_start)
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyAccess($badgeUid, $location, $actionType)
    {
        // 1. Find Badge
        $badge = DB::table('pool_schema.access_badges')
                    ->where('badge_uid', $badgeUid)
                    ->first(); // Status check later for specific error msg

        if (!$badge) {
             $this->logAccess($badgeUid, null, $location, $actionType, 'denied', 'Badge inconnu');
             return ['success' => false, 'message' => 'Badge inconnu.'];
        }

        if ($badge->status !== 'active') {
             $this->logAccess($badgeUid, $badge->staff_id, $location, $actionType, 'denied', 'Badge inactif');
             return ['success' => false, 'message' => 'Badge inactif.'];
        }

        // 2. Check Permissions / Staff Type
        // For Proof of Concept, we allow any Active Staff to access "Doors" and "Maintenance".
        // In real world, we would check Staff->role->permissions for "access_tech_room" etc.
        
        // If badge belongs to a Member (not Staff)
        if (!$badge->staff_id) {
             // Members might have access to "Main Entrance" but not "Pump Room"
             if ($location === 'Technical Room' || $actionType === 'maintenance_start') {
                 $this->logAccess($badgeUid, null, $location, $actionType, 'denied', 'Accès réservé au personnel');
                 return ['success' => false, 'message' => 'Accès refusé (Personnel uniquement).'];
             }
        }

        // 3. Grant Access
        $this->logAccess($badgeUid, $badge->staff_id, $location, $actionType, 'granted', null);

        // Optional: If Maintenance, could create a "Maintenance Session" record here if needed.
        // For now, just logging is enough as per "Enregistrer une intervention".

        return ['success' => true, 'message' => 'Accès autorisé.', 'staff_id' => $badge->staff_id];
    }

    private function logAccess($badgeUid, $staffId, $location, $actionType, $decision, $reason = null)
    {
        DB::table('pool_schema.access_logs')->insert([
            'badge_uid' => $badgeUid,
            'staff_id' => $staffId,
            'access_time' => Carbon::now(),
            'access_decision' => $decision,
            'denial_reason' => $reason,
            'action_type' => $actionType,
            'location' => $location,
        ]);
    }
}
