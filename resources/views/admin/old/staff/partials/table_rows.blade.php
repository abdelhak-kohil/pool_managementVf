@forelse($staff as $member)
  <tr class="hover:bg-gray-50">
    <td class="border p-3">{{ $member->staff_id }}</td>
    <td class="border p-3">{{ $member->first_name }} {{ $member->last_name }}</td>
    <td class="border p-3">{{ $member->username }}</td>
    <td class="border p-3 capitalize">{{ $member->role->role_name ?? 'N/A' }}</td>
    <td class="border p-3 text-center">
      <button
        class="toggle-btn px-3 py-1 rounded text-white text-xs font-medium
        {{ $member->is_active ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }}"
        data-id="{{ $member->staff_id }}"
        data-active="{{ $member->is_active ? '1' : '0' }}">
        {{ $member->is_active ? 'Active' : 'Inactive' }}
      </button>
    </td>
    <td class="border p-3 text-center">
      <a href="{{ route('staff.edit', $member->staff_id) }}" class="text-blue-600 hover:underline">Edit</a> |
      <button onclick="confirmDelete({{ $member->staff_id }})" class="text-red-600 hover:underline">Delete</button>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="6" class="text-center text-gray-500 p-4">No staff found.</td>
  </tr>
@endforelse
