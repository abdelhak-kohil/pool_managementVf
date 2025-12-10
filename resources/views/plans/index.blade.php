@extends('layouts.app')
@section('title', 'Plans d’abonnement')

@section('content')
<div x-data="plansSearch()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">📦 Plans d’abonnement</h2>
    <a href="{{ route('plans.create') }}" 
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Nouveau Plan
    </a>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" placeholder="Rechercher un plan..."
           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 shadow-sm transition duration-200">
    <svg xmlns="http://www.w3.org/2000/svg" 
         class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
  </div>

  <!-- ===== TABLE ===== -->
  <div class="bg-white rounded-xl shadow overflow-hidden border border-gray-100">
    <table class="min-w-full text-left text-gray-800">
      <thead class="bg-gray-50 border-b">
        <tr>
          <th class="py-3 px-4 font-medium">Nom du plan</th>
          <th class="py-3 px-4 font-medium">Type</th>

          <th class="py-3 px-4 font-medium text-center">Visites/Semaine</th>
          <th class="py-3 px-4 font-medium text-center">Durée (mois)</th>
          <th class="py-3 px-4 font-medium text-center">Statut</th>
          <th class="py-3 px-4 font-medium text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($plans as $plan)
        <tr class="hover:bg-blue-50 transition" x-show="matchesSearch('{{ strtolower($plan->plan_name) }}')">
          <td class="py-3 px-4 font-medium">{{ $plan->plan_name }}</td>
          <td class="py-3 px-4">{{ ucfirst(str_replace('_', ' ', $plan->plan_type)) }}</td>

          <td class="py-3 px-4 text-center">{{ $plan->visits_per_week ?? '-' }}</td>
          <td class="py-3 px-4 text-center">{{ $plan->duration_months ?? '-' }}</td>
          <td class="py-3 px-4 text-center">
            <span class="px-2 py-1 text-xs rounded-full 
              {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
              {{ $plan->is_active ? 'Actif' : 'Inactif' }}
            </span>
          </td>
          <td class="py-3 px-4 text-right">
            <div class="flex justify-end gap-3">
              <a href="{{ route('plans.edit', $plan->plan_id) }}" class="text-yellow-600 hover:text-yellow-800 font-medium">✏️ Modifier</a>
              @if (Auth::user()->role->role_name === 'Admin')
              <form action="{{ route('plans.destroy', $plan->plan_id) }}" method="POST" class="inline-block delete-form">
                @csrf @method('DELETE')
                <button type="button" class="text-red-600 hover:text-red-800 font-medium delete-btn">🗑 Supprimer</button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center py-6 text-gray-500">Aucun plan trouvé.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Alpine & SweetAlert -->


<script>
function plansSearch() {
  return {
    search: '',
    matchesSearch(text) {
      return text.includes(this.search.toLowerCase());
    }
  }
}

// SweetAlert Delete Confirmation
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Supprimer ce plan ?',
      text: 'Cette action est irréversible !',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Oui, supprimer',
      cancelButtonText: 'Annuler',
      confirmButtonColor: '#d33'
    }).then(result => {
      if (result.isConfirmed) form.submit();
    });
  }
});
</script>
@endsection
