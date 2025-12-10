@extends('layouts.app')
@section('title', 'Staff Management')

@section('content')
<div x-data="staffTable()">
  <!-- ===== HEADER & FILTERS ===== -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Staff Members</h2>
    
    <div class="flex flex-wrap gap-2">
       <!-- View Toggle -->
       <div class="bg-gray-100 p-1 rounded-lg flex items-center mr-2">
            <button @click="view = 'list'" :class="view === 'list' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="p-2 rounded-md transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <button @click="view = 'grid'" :class="view === 'grid' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700'" class="p-2 rounded-md transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            </button>
       </div>

       <!-- Action Buttons -->
        <a href="{{ route('staff.hr.dashboard') }}"
           class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition flex items-center gap-2 text-sm">
           ⏱️ Présence
        </a>
        <a href="{{ route('staff.planning.index') }}"
           class="bg-purple-600 text-white px-4 py-2 rounded-lg shadow hover:bg-purple-700 transition flex items-center gap-2 text-sm">
           📅 Planning
        </a>
        <a href="{{ route('staff.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition flex items-center gap-2 text-sm">
           ➕ Add Staff
        </a>
    </div>
  </div>

  <!-- ===== FILTERS TOOLBAR ===== -->
  <form method="GET" action="{{ route('staff.index') }}" class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 mb-6 items-center">
      
      <!-- Search -->
      <div class="relative flex-1 w-full">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom, email..."
               class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 transition">
        <svg class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
        </svg>
      </div>

      <!-- Role Filter -->
      <div class="w-full md:w-48">
          <select name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
              <option value="">Tous les Rôles</option>
              @foreach($roles as $role)
                <option value="{{ $role->role_name }}" {{ request('role') == $role->role_name ? 'selected' : '' }}>
                    {{ ucfirst($role->role_name) }}
                </option>
              @endforeach
          </select>
      </div>

      <!-- Status Filter -->
      <div class="w-full md:w-40">
           <select name="status" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
              <option value="">Tous statuts</option>
              <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
              <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
          </select>
      </div>

      <!-- Submit & Reset -->
      <div class="flex gap-2">
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">Filtrer</button>
          @if(request()->anyFilled(['search', 'role', 'status']))
            <a href="{{ route('staff.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm flex items-center">
                ✖
            </a>
          @endif
      </div>
  </form>

  @if(session('success'))
    <div class="bg-green-100 text-green-700 p-3 rounded mb-4 shadow-sm border border-green-200">{{ session('success') }}</div>
  @endif

  <!-- ===== LIST VIEW (TABLE) ===== -->
  <div x-show="view === 'list'" class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100 animate-fade-in">
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
            @forelse($staff as $s)
            <tr class="hover:bg-blue-50 transition">
                <td class="px-4 py-3 whitespace-nowrap text-gray-500 text-sm">{{ $s->staff_id }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs mr-3 border border-blue-200">
                            {{ substr($s->first_name, 0, 1) }}{{ substr($s->last_name, 0, 1) }}
                        </div>
                        <div class="text-sm font-medium text-gray-900">{{ $s->first_name }} {{ $s->last_name }}</div>
                    </div>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $s->username }}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    @php
                        $roleName = $s->role->role_name ?? '';
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ ucfirst($roleName) }}
                    </span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  @if($s->is_active)
                    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-xs font-semibold">Active</span>
                  @else
                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded-full text-xs font-semibold">Inactive</span>
                  @endif
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">
                    <a href="{{ route('staff.show', $s->staff_id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Vue</a>
                    <a href="{{ route('staff.edit', $s->staff_id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                    <form action="{{ route('staff.destroy', $s->staff_id) }}" method="POST" class="delete-form inline-block">
                        @csrf @method('DELETE')
                        <button type="button" class="text-red-600 hover:text-red-900 delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 italic bg-gray-50">Aucun membre trouvé pour ces critères.</td>
            </tr>
            @endforelse
        </tbody>
      </table>
  </div>

  <!-- ===== GRID VIEW (CARDS) ===== -->
  <div x-show="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-fade-in" style="display: none;">
      @forelse($staff as $s)
      <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition border border-gray-100 overflow-hidden group">
          <div class="p-6">
              <div class="flex justify-between items-start mb-4">
                  <div class="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg shadow-sm group-hover:scale-105 transition-transform">
                      {{ substr($s->first_name, 0, 1) }}{{ substr($s->last_name, 0, 1) }}
                  </div>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $s->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                     {{ $s->is_active ? 'Actif' : 'Inactif' }}
                  </span>
              </div>
              <h3 class="text-lg font-bold text-gray-900 text-ellipsis overflow-hidden whitespace-nowrap" title="{{ $s->full_name }}">
                  {{ $s->first_name }} {{ $s->last_name }}
              </h3>
              <p class="text-sm text-gray-500 mb-4">{{ $s->role->role_name ?? 'Staff' }}</p>
              
              <div class="flex items-center text-xs text-gray-400 gap-2 mb-4">
                  <span class="flex items-center gap-1">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                      {{ $s->username }}
                  </span>
              </div>
          </div>
          <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-between items-center">
               <a href="{{ route('staff.show', $s->staff_id) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Voir Profil &rarr;</a>
               <div class="flex gap-2">
                  <a href="{{ route('staff.edit', $s->staff_id) }}" class="text-gray-400 hover:text-blue-600 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></a>
               </div>
          </div>
      </div>
      @empty
      <div class="col-span-full py-12 text-center text-gray-500">
          Aucun résultat ne correspond à votre recherche.
      </div>
      @endforelse
  </div>
</div>

<script>
function staffTable() {
    return {
        view: 'list', // 'list' or 'grid'
        // Search is now handled by backend controller, frontend x-model removed to rely on form submit
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
