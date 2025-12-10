<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'members.view', 'members.create', 'members.edit', 'members.delete',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
            'subscriptions.view', 'subscriptions.create', 'subscriptions.edit', 'subscriptions.delete',
            'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            'activities.view', 'activities.create', 'activities.edit', 'activities.delete',
            'schedule.view', 'schedule.create', 'schedule.edit', 'schedule.delete',
            'coaches.view',
            'finance.view_stats'
        ];

        // 1. Insert Permissions
        foreach ($permissions as $perm) {
            DB::table('pool_schema.permissions')->insertOrIgnore(['permission_name' => $perm]);
        }

        // 2. Get Role IDs
        $roles = DB::table('pool_schema.roles')->pluck('role_id', 'role_name');

        // 3. Define Assignments
        $assignments = [
            'admin' => $permissions, // All permissions
            'financer' => [
                'finance.view_stats',
                'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
                'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
                'subscriptions.view', 'members.view'
            ],
            'receptionniste' => [
                'members.view', 'members.create', 'members.edit',
                'subscriptions.view', 'subscriptions.create', 'subscriptions.edit',
                'payments.create',
                'schedule.view',
                'coaches.view'
            ],
            'coach' => [
                'schedule.view',
                'coaches.view'
            ]
        ];

        // 4. Assign Permissions to Roles
        foreach ($assignments as $roleName => $rolePerms) {
            if (isset($roles[$roleName])) {
                $roleId = $roles[$roleName];
                foreach ($rolePerms as $permName) {
                    $permId = DB::table('pool_schema.permissions')->where('permission_name', $permName)->value('permission_id');
                    if ($permId) {
                        DB::table('pool_schema.role_permissions')->insertOrIgnore([
                            'role_id' => $roleId,
                            'permission_id' => $permId
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: truncate permissions or role_permissions if needed
        // DB::table('pool_schema.role_permissions')->truncate();
        // DB::table('pool_schema.permissions')->truncate();
    }
};
