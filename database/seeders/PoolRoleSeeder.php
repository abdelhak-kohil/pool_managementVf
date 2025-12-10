<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class PoolRoleSeeder extends Seeder
{
    public function run()
    {
        // Create Permissions
        $permissions = [
            'pool.view',
            'pool.manage_water',
            'pool.manage_equipment',
            'pool.manage_maintenance',
            'pool.manage_chemicals',
            'pool.manage_incidents',
            'pool.perform_tasks',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['permission_name' => $permName]);
        }

        // Create Roles
        // Create Roles
        $maintenanceManager = Role::firstOrCreate(['role_name' => 'maintenance_manager']);
        $poolTechnician = Role::firstOrCreate(['role_name' => 'pool_technician']);
        Role::firstOrCreate(['role_name' => 'directeur']);
        Role::firstOrCreate(['role_name' => 'financer']);
        Role::firstOrCreate(['role_name' => 'réceptionniste']);

        // Assign Permissions
        
        // Admin gets everything
        $admin = Role::firstOrCreate(['role_name' => 'admin']);
        if ($admin) {
            $admin->permissions()->syncWithoutDetaching(Permission::where('permission_name', 'like', 'pool.%')->pluck('permission_id'));
        }

        // Maintenance Manager gets everything
        $maintenanceManager->permissions()->sync(Permission::where('permission_name', 'like', 'pool.%')->pluck('permission_id'));

        // Technician gets operational permissions
        $techPermissions = Permission::whereIn('permission_name', [
            'pool.view',
            'pool.manage_water',
            'pool.perform_tasks',
            'pool.manage_incidents',
            'pool.manage_chemicals',
        ])->pluck('permission_id');
        
        $poolTechnician->permissions()->sync($techPermissions);
    }
}
