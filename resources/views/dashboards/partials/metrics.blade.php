<div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
  <div>
    <div class="text-sm text-gray-500">Staff</div>
    <div class="text-2xl font-semibold text-gray-800">{{ $staffCount }}</div>
  </div>
  <a href="{{ route('staff.index') }}" class="bg-blue-50 text-blue-600 px-3 py-1 rounded">Manage</a>
</div>

<div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
  <div>
    <div class="text-sm text-gray-500">Roles</div>
    <div class="text-2xl font-semibold text-gray-800">{{ $roleCount }}</div>
  </div>
  <a href="{{ route('roles.index') }}" class="bg-green-50 text-green-700 px-3 py-1 rounded">Manage</a>
</div>

<div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
  <div>
    <div class="text-sm text-gray-500">Permissions</div>
    <div class="text-2xl font-semibold text-gray-800">{{ $permissionCount }}</div>
  </div>
  <a href="{{ route('permissions.index') }}" class="bg-purple-50 text-purple-700 px-3 py-1 rounded">Manage</a>
</div>

<div class="bg-white shadow rounded-lg p-4 flex justify-between items-center">
  <div>
    <div class="text-sm text-gray-500">Audit Logs</div>
    <div class="text-2xl font-semibold text-gray-800">{{ $auditCount }}</div>
  </div>
  <a href="{{ route('audit.index') }}" class="bg-yellow-50 text-yellow-700 px-3 py-1 rounded">View</a>
</div>
