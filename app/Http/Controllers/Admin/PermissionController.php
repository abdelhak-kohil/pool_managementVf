<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('permission_id', 'asc')->get();
        return view('permissions.index', compact('permissions'));
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate(['permission_name' => 'required|unique:permissions,permission_name']);
        Permission::create($request->only('permission_name'));
        return redirect()->route('permissions.index')->with('success', 'Permission créée avec succès.');
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['permission_name' => 'required']);
        $permission = Permission::findOrFail($id);
        $permission->update(['permission_name' => $request->permission_name]);
        return redirect()->route('permissions.index')->with('success', 'Permission mise à jour avec succès.');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission supprimée.');
    }
}
