@extends('layouts.app')
@section('title', 'Manage Staff')

@section('content')
<div class="bg-white shadow rounded-lg p-6">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
    <div>
      <h2 class="text-xl font-semibold text-gray-800">Staff Members</h2>
      <p class="text-gray-500 text-sm">Search and manage your staff instantly.</p>
    </div>
    <a href="{{ route('staff.create') }}"
       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">+ Add Staff</a>
  </div>

  <!-- Filters + Search -->
  <form id="filterForm" class="flex flex-wrap gap-2 mb-4">
    <input type="text" id="searchInput" name="search" placeholder="Search name or username"
           class="border rounded-lg px-3 py-2 w-full sm:w-48 focus:ring focus:ring-blue-200">

    <select id="roleFilter" name="role_id" class="border rounded-lg px-3 py-2 w-full sm:w-44">
      <option value="all">All Roles</option>
      @foreach($roles as $role)
        <option value="{{ $role->role_id }}">{{ ucfirst($role->role_name) }}</option>
      @endforeach
    </select>

    <select id="statusFilter" name="status" class="border rounded-lg px-3 py-2 w-full sm:w-44">
      <option value="all">All Statuses</option>
      <option value="active">Active</option>
      <option value="inactive">Inactive</option>
    </select>
  </form>

  <!-- Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full border border-gray-200 rounded-lg text-sm">
      <thead class="bg-gray-50 text-gray-700">
        <tr>
          <th class="border p-3 text-left">#</th>
          <th class="border p-3 text-left">Name</th>
          <th class="border p-3 text-left">Username</th>
          <th class="border p-3 text-left">Role</th>
          <th class="border p-3 text-center">Status</th>
          <th class="border p-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="staffTableBody">
        @include('admin.staff.partials.table_rows', ['staff' => $staff])
      </tbody>
    </table>
  </div>
</div>

<!-- ✅ Delete Confirmation -->
<script>
function confirmDelete(id) {
  Swal.fire({
    title: 'Delete Staff?',
    text: 'This will permanently remove the staff member.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete'
  }).then((result) => {
    if (result.isConfirmed) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `/admin/staff/${id}`;
      form.innerHTML = `@csrf @method('DELETE')`;
      document.body.appendChild(form);
      form.submit();
    }
  });
}
</script>

<!-- ✅ Real-Time Search Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  const roleFilter = document.getElementById('roleFilter');
  const statusFilter = document.getElementById('statusFilter');
  const staffTableBody = document.getElementById('staffTableBody');

  async function fetchStaff() {
    const query = searchInput.value;
    const role_id = roleFilter.value;
    const status = statusFilter.value;

    const params = new URLSearchParams({ query, role_id, status });
    const res = await fetch(`/admin/staff/search?${params.toString()}`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();
    staffTableBody.innerHTML = data.html;
  }

  // Live search + filter (debounced)
  let timeout = null;
  [searchInput, roleFilter, statusFilter].forEach(el => {
    el.addEventListener('input', () => {
      clearTimeout(timeout);
      timeout = setTimeout(fetchStaff, 300);
    });
  });
});
</script>
@endsection
