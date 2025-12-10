<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;

class RolePermissionController extends Controller
{
    /**
     * Show the Role-Permission management page.
     */
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('role_id')->get();
        $permissions = Permission::orderBy('permission_id')->get();

        return view('admin.role_permissions', compact('roles', 'permissions'));
    }

    /**
     * Update role-permission assignments.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'permissions' => 'array',
        ]);

        // Remove all existing mappings
        DB::table('pool_schema.role_permissions')->truncate();

        // Re-insert the checked ones
        foreach ($data['permissions'] ?? [] as $roleId => $permIds) {
            foreach ($permIds as $permissionId) {
                DB::table('pool_schema.role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Permissions mises à jour avec succès.');
    }
}
