<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class AssignMemberPermissionsSeeder extends Seeder
{
    public function run()
    {
        // 1. Define CRM Permissions
        $permissions = [
            'members.view',
            'members.create',
            'members.edit',
            'members.delete',
            'subscriptions.view',
            'subscriptions.create',
            'subscriptions.edit',
            'subscriptions.delete',
            'payments.create',
            // Activities & Plans (Catalog)
            'activities.view',
            'activities.create',
            'activities.edit',
            'activities.delete',
            'plans.view',
            'plans.create',
            'plans.edit',
            'plans.delete',
            // Planning & Reservations (Operations)
            'schedule.view',
            'reservations.view',
            'reservations.create',
            'reservations.delete', // Used for cancellation
        ];

        // 2. Create Permissions
        $permIds = [];
        foreach ($permissions as $permName) {
            $p = Permission::firstOrCreate(['permission_name' => $permName]);
            $permIds[] = $p->permission_id;
        }

        // 3. Find Roles
        $receptionist = Role::where('role_name', 'receptionniste')->first();
        if (!$receptionist) {
             $receptionist = Role::where('role_name', 'réceptionniste')->orWhere('role_name', 'receprionist')->first();
        }
        
        $admin = Role::where('role_name', 'admin')->first();

        // 4. Assign Permissions
        if ($receptionist) {
            $receptionist->permissions()->syncWithoutDetaching($permIds);
            $this->command->info("Granted CRM + Activities/Planning permissions to role: " . $receptionist->role_name);
        } else {
            $this->command->warn("Role 'receprionist' or 'réceptionniste' not found.");
        }

        if ($admin) {
            $admin->permissions()->syncWithoutDetaching($permIds);
        }
    }
}
