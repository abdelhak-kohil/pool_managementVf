@extends('layouts.app')
@section('title', 'Permissions Management')

@section('content')
<div x-data="permissionsTable()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Permissions</h2>
    <a href="{{ route('permissions.create') }}"
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Add Permission
    </a>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" placeholder="Search permissions..."
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
            <th class="py-3 px-4 font-medium">Permission Name</th>
            <th class="py-3 px-4 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($permissions as $permission)
            <tr class="hover:bg-blue-50 transition" x-show="matchesSearch('{{ strtolower($permission->permission_name) }}')">
                <td class="py-3 px-4 text-gray-500">{{ $permission->permission_id }}</td>
                <td class="py-3 px-4 font-medium">{{ $permission->permission_name }}</td>
                <td class="py-3 px-4 text-right">
                    <div class="flex justify-end gap-3">
                        <a href="{{ route('permissions.edit', $permission->permission_id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Edit</a>
                        <form action="{{ route('permissions.destroy', $permission->permission_id) }}" method="POST" class="delete-form inline-block">
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
function permissionsTable() {
    return {
        search: '',
        matchesSearch(name) {
            return name.includes(this.search.toLowerCase());
        }
    }
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Delete Permission?',
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
