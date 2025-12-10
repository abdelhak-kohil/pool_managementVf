@extends('layouts.app')
@section('title', 'Manage Permissions')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-3">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Permissions Management</h2>
      <p class="text-sm text-gray-500">Define and manage system permissions.</p>
    </div>
    <button id="addPermBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">+ Add Permission</button>
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
          <th class="border p-3 text-left">Permission Name</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($permissions as $perm)
          <tr class="hover:bg-gray-50">
            <td class="border p-3">{{ $perm->permission_id }}</td>
            <td class="border p-3">{{ $perm->permission_name }}</td>
            <td class="border p-3 text-center">
              <button class="text-blue-600 hover:underline" onclick="editPerm({{ $perm->permission_id }}, '{{ $perm->permission_name }}')">Edit</button> |
              <button class="text-red-600 hover:underline" onclick="deletePerm({{ $perm->permission_id }})">Delete</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center text-gray-500 p-4">No permissions found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- ✅ Add/Edit Permission Modal -->
<form id="permForm" method="POST" action="{{ route('permissions.store') }}">
  @csrf
  <input type="hidden" name="id" id="perm_id">
  <div id="permModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
      <h3 id="permTitle" class="text-lg font-semibold text-gray-800 mb-4">Add Permission</h3>
      <input type="text" name="permission_name" id="perm_name" placeholder="Enter permission name"
             class="w-full border rounded-lg px-3 py-2 mb-4 focus:ring focus:ring-blue-200 focus:border-blue-500" required>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closePermModal()" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700">Save</button>
      </div>
    </div>
  </div>
</form>

<script>
function closePermModal() {
  document.getElementById('permModal').classList.add('hidden');
  document.getElementById('permForm').reset();
  document.getElementById('perm_id').value = '';
}

document.getElementById('addPermBtn').addEventListener('click', () => {
  document.getElementById('permTitle').textContent = 'Add Permission';
  document.getElementById('permForm').action = '{{ route('permissions.store') }}';
  document.getElementById('permModal').classList.remove('hidden');
});

function editPerm(id, name) {
  document.getElementById('permTitle').textContent = 'Edit Permission';
  document.getElementById('perm_name').value = name;
  document.getElementById('perm_id').value = id;
  document.getElementById('permForm').action = `/admin/permissions/${id}/update`;
  document.getElementById('permModal').classList.remove('hidden');
}


@endsection
