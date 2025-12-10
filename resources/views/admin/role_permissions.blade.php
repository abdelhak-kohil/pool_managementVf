@extends('layouts.app')
@section('title', 'Assign Permissions')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <h2 class="text-xl font-semibold text-gray-800 mb-4">Assign Permissions to Roles</h2>
  <form action="{{ route('admin.role_permissions.update') }}" method="POST">
    @csrf

    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 rounded-lg text-sm">
        <thead class="bg-gray-50 text-gray-700">
          <tr>
            <th class="border p-3 text-left">Permission</th>
            @foreach($roles as $role)
              <th class="border p-3 text-center capitalize">{{ $role->role_name }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach($permissions as $perm)
            <tr class="hover:bg-gray-50">
              <td class="border p-3">{{ $perm->permission_name }}</td>
              @foreach($roles as $role)
                <td class="border p-3 text-center">
                  <input type="checkbox"
                         name="permissions[{{ $role->role_id }}][]"
                         value="{{ $perm->permission_id }}"
                         {{ in_array($perm->permission_id, $rolePermissions[$role->role_id] ?? []) ? 'checked' : '' }}
                         class="rounded text-blue-600 focus:ring-blue-400">
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="flex justify-end mt-5">
      <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Save Changes</button>
    </div>
  </form>
</div>


@endsection
