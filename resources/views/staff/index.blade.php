@extends('layouts.app')
@section('title', 'Staff Management')

@section('content')
<div x-data="staffTable()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Staff Members</h2>
    <div class="flex gap-3">
        <a href="{{ route('staff.planning.index') }}"
           class="bg-purple-600 text-white px-5 py-2 rounded-lg shadow hover:bg-purple-700 transition flex items-center gap-2">
           📅 Planning & Absences
        </a>
        <a href="{{ route('staff.create') }}"
           class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition flex items-center gap-2">
           ➕ Add Staff
        </a>
    </div>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" placeholder="Search staff..."
           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 shadow-sm transition duration-200">
    <svg xmlns="http://www.w3.org/2000/svg"
         class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
  </div>

  @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
  @endif

  <!-- ===== TABLE ===== -->
  <div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
      <table class="min-w-full text-left text-gray-800">
        <thead class="bg-gray-50 border-b">
            <tr>
            <th class="py-3 px-4 font-medium">#</th>
            <th class="py-3 px-4 font-medium">Name</th>
            <th class="py-3 px-4 font-medium">Username</th>
            <th class="py-3 px-4 font-medium">Role</th>
            <th class="py-3 px-4 font-medium">Status</th>
            <th class="py-3 px-4 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($staff as $s)
            <tr class="hover:bg-blue-50 transition" x-show="matchesSearch('{{ strtolower($s->first_name . ' ' . $s->last_name . ' ' . $s->username) }}')">
                <td class="py-3 px-4 text-gray-500">{{ $s->staff_id }}</td>
                <td class="py-3 px-4 font-medium">{{ $s->first_name }} {{ $s->last_name }}</td>
                <td class="py-3 px-4">{{ $s->username }}</td>
                <td class="py-3 px-4">
                    @php
                        $roleName = $s->role->role_name ?? '';
                        $roleColor = match(strtolower($roleName)) {
                            'admin', 'administrator' => 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
                            'manager' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10',
                            'staff' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-700/10',
                            'member', 'user' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
                            default => 'bg-gray-50 text-gray-600 ring-1 ring-gray-500/10'
                        };
                    @endphp
                    <span class="{{ $roleColor }} px-2.5 py-0.5 rounded-md text-xs font-medium inline-flex items-center">
                        {{ $roleName ?: '-' }}
                    </span>
                </td>
                <td class="py-3 px-4">
                  @if($s->is_active)
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-medium">Active</span>
                  @else
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-medium">Inactive</span>
                  @endif
                </td>
                <td class="py-3 px-4 text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('staff.edit', $s->staff_id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Edit</a>
                        <form action="{{ route('staff.destroy', $s->staff_id) }}" method="POST" class="delete-form inline-block">
                            @csrf @method('DELETE')
                            <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
      </table>
  </div>
</div>

<!-- Alpine & SweetAlert -->


<script>
function staffTable() {
    return {
        search: '',
        matchesSearch(text) {
            return text.includes(this.search.toLowerCase());
        }
    }
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Delete Staff Member?',
      text: 'This action cannot be undone!',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete it!',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  }
});
</script>
@endsection
