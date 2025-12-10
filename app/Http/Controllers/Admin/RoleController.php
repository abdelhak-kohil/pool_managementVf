<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Role;
use App\Models\Admin\Permission;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::orderBy('role_id', 'asc')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request)
    {
        $request->validate(['role_name' => 'required|unique:roles,role_name']);
        Role::create($request->only('role_name'));
        return redirect()->route('roles.index')->with('success', 'Rôle créé avec succès.');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['role_name' => 'required']);
        $role = Role::findOrFail($id);
        $role->update(['role_name' => $request->role_name]);
        return redirect()->route('roles.index')->with('success', 'Rôle mis à jour avec succès.');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Rôle supprimé.');
    }

    public function managePermissions($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::orderBy('permission_name')->get();
        $assigned = $role->permissions->pluck('permission_id')->toArray();
        return view('roles.permissions', compact('role', 'permissions', 'assigned'));
    }

    public function updatePermissions(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->sync($request->permissions ?? []);
        return redirect()->route('roles.index')->with('success', 'Permissions mises à jour avec succès.');
    }
}
