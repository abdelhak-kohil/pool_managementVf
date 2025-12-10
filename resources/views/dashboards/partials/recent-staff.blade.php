<div class="overflow-x-auto">
  <table class="min-w-full text-sm">
    <thead class="bg-gray-50 text-gray-600">
      <tr>
        <th class="p-2 text-left">#</th>
        <th class="p-2 text-left">Name</th>
        <th class="p-2 text-left">Role</th>
        <th class="p-2 text-center">Status</th>
        <th class="p-2 text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      @forelse($recentStaff as $member)
        <tr class="border-b hover:bg-gray-50">
          <td class="p-2">{{ $member->staff_id }}</td>
          <td class="p-2">{{ $member->first_name }} {{ $member->last_name }}
            <div class="text-xs text-gray-400">{{ $member->username }}</div>
          </td>
          <td class="p-2 capitalize">{{ $member->role->role_name ?? 'N/A' }}</td>
          <td class="p-2 text-center">
            <button data-id="{{ $member->staff_id }}" class="toggle-btn px-3 py-1 rounded text-white text-xs {{ $member->is_active ? 'bg-green-600' : 'bg-red-600' }}">
              {{ $member->is_active ? 'Active' : 'Inactive' }}
            </button>
          </td>
          <td class="p-2 text-center">
            <a href="{{ route('staff.edit', $member->staff_id) }}" class="text-blue-600 hover:underline text-sm">Edit</a>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-center text-gray-500 p-4">No staff found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
