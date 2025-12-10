@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<div class="space-y-6">
  {{-- Summary cards --}}
  <div id="metricsRow" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @include('dashboards.partials.metrics')
  </div>

  {{-- Quick actions + search --}}
  <div class="bg-white shadow rounded-lg p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div class="flex items-center flex-wrap gap-3">
      <a href="{{ route('staff.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">+ Staff</a>
      <a href="{{ route('roles.index') }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Roles</a>
      <a href="{{ route('permissions.index') }}" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">Permissions</a>
      <a href="{{ route('admin.role_permissions') }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Assign</a>
    </div>
    <div class="flex items-center gap-3 w-full md:w-auto">
      <input id="dashSearch" type="text" placeholder="Quick staff search" class="border rounded-lg px-3 py-2 w-full md:w-64 focus:ring focus:ring-blue-200">
      <button id="dashSearchBtn" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900">Search</button>
      <button id="refreshBtn" title="Refresh" class="bg-gray-100 px-3 py-2 rounded border hover:bg-gray-50">⟳</button>
    </div>
  </div>

  {{-- Two-column grid --}}
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Left: Staff --}}
    <div class="bg-white shadow rounded-lg p-4">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-lg font-semibold text-gray-800">Recent Staff</h3>
        <a href="{{ route('staff.index') }}" class="text-sm text-gray-500 hover:underline">See all</a>
      </div>
      <div id="recentStaffContainer">@include('dashboards.partials.recent-staff')</div>
    </div>

    {{-- Right: Activity --}}
    <div class="bg-white shadow rounded-lg p-4">
      <div class="flex justify-between items-center mb-3">
        <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
        <a href="{{ route('audit.index') }}" class="text-sm text-gray-500 hover:underline">See all</a>
      </div>
      <div id="recentAuditsContainer">@include('dashboards.partials.recent-audits')</div>
    </div>
  </div>
</div>

@include('admin.audit.partials.details-modal')

{{-- JS: search, refresh, toggle --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  const searchInput = document.getElementById('dashSearch');
  const searchBtn = document.getElementById('dashSearchBtn');
  const refreshBtn = document.getElementById('refreshBtn');

  function goSearch() {
    const q = encodeURIComponent(searchInput.value.trim());
    window.location.href = q ? '{{ route("staff.index") }}?search=' + q : '{{ route("staff.index") }}';
  }
  searchBtn.onclick = goSearch;
  searchInput.onkeypress = e => { if (e.key === 'Enter') { e.preventDefault(); goSearch(); } };

  // Refresh dashboard stats + tables
  refreshBtn.onclick = async () => {
    try {
      const res = await fetch('{{ route("admin.dashboard") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      document.querySelector('#metricsRow').innerHTML = `
        ${metricCard('Staff', data.staffCount, 'bg-blue-50 text-blue-600', '{{ route("staff.index") }}')}
        ${metricCard('Roles', data.roleCount, 'bg-green-50 text-green-700', '{{ route("roles.index") }}')}
        ${metricCard('Permissions', data.permissionCount, 'bg-purple-50 text-purple-700', '{{ route("permissions.index") }}')}
        ${metricCard('Audits', data.auditCount, 'bg-yellow-50 text-yellow-700', '{{ route("audit.index") }}')}
      `;
      document.querySelector('#recentStaffContainer').innerHTML = data.recentStaffHtml;
      document.querySelector('#recentAuditsContainer').innerHTML = data.recentAuditsHtml;
      attachToggleListeners();
      showToast('success', 'Dashboard refreshed');
    } catch (err) {
      showToast('error', 'Failed to refresh');
    }
  };

  function metricCard(title, count, color, url) {
    return `
    <div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
      <div>
        <div class="text-sm text-gray-500">${title}</div>
        <div class="text-2xl font-semibold text-gray-800">${count}</div>
      </div>
      <a href="${url}" class="inline-block px-3 py-1 rounded ${color}">Manage</a>
    </div>`;
  }

  // Toggle staff active/inactive
  async function toggleStatus(id, btn) {
    try {
      const res = await fetch(`/admin/staff/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
      });
      const data = await res.json();
      if (data.success) {
        btn.textContent = data.is_active ? 'Active' : 'Inactive';
        btn.classList.toggle('bg-green-600', data.is_active);
        btn.classList.toggle('bg-red-600', !data.is_active);
        showToast('success', 'Status updated');
      } else showToast('error', 'Could not update');
    } catch (e) { showToast('error', 'Server error'); }
  }

  function attachToggleListeners() {
    document.querySelectorAll('.toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => toggleStatus(btn.dataset.id, btn));
    });
  }

  attachToggleListeners();
});
</script>
@endsection
