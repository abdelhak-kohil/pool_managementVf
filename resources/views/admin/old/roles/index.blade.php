@extends('layouts.app')
@section('title', 'Manage Roles')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Roles Management</h2>
      <p class="text-sm text-gray-500">Create, update, or delete staff roles.</p>
    </div>
    <button id="addRoleBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Add Role</button>
  </div>

  @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-700 p-3 rounded">
      {{ session('success') }}
    </div>
  @endif

  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">Role Name</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($roles as $role)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $role->role_id }}</td>
            <td class="border p-3 capitalize">{{ $role->role_name }}</td>
            <td class="border p-3 text-center">
              <button class="text-blue-600 hover:underline" onclick="editRole({{ $role->role_id }}, '{{ $role->role_name }}')">Edit</button> |
              <button class="text-red-600 hover:underline" onclick="deleteRole({{ $role->role_id }})">Delete</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center text-gray-500 p-4">No roles found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- ✅ Add/Edit Role Modal -->
<form id="roleForm" method="POST" action="{{ route('roles.store') }}">
  @csrf
  <input type="hidden" name="id" id="role_id">
  <div id="roleModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
      <h3 id="modalTitle" class="text-lg font-semibold text-gray-800 mb-4">Add Role</h3>
      <input type="text" name="role_name" id="role_name" placeholder="Enter role name"
             class="w-full border rounded-lg px-3 py-2 mb-4 focus:ring focus:ring-blue-200 focus:border-blue-500" required>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Save</button>
      </div>
    </div>
  </div>
</form>

<script>
function closeModal() {
  document.getElementById('roleModal').classList.add('hidden');
  document.getElementById('roleForm').reset();
  document.getElementById('role_id').value = '';
}

document.getElementById('addRoleBtn').addEventListener('click', () => {
  document.getElementById('modalTitle').textContent = 'Add Role';
  document.getElementById('roleForm').action = '{{ route('roles.store') }}';
  document.getElementById('roleModal').classList.remove('hidden');
});

function editRole(id, name) {
  document.getElementById('modalTitle').textContent = 'Edit Role';
  document.getElementById('role_name').value = name;
  document.getElementById('role_id').value = id;
  document.getElementById('roleForm').action = `/admin/roles/${id}/update`;
  document.getElementById('roleModal').classList.remove('hidden');
}


@endsection
