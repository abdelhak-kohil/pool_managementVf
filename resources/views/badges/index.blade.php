@extends('layouts.app')
@section('title', 'Badges d’Accès')

@section('content')
<div x-data="badgeModal()">
  <!-- ===== HEADER ===== -->
  <!-- ===== HEADER ===== -->
  <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <div>
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <span>🎫</span> Gestion des Badges
        </h2>
        <p class="text-gray-500 text-sm mt-1">Gérez les badges d'accès et leur statut.</p>
    </div>
    <div class="flex gap-3">
        <a href="{{ route('portal.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition font-medium shadow-sm flex items-center gap-2">
            <span>⬅️</span> Portail
        </a>
        <a href="{{ route('badges.create') }}"
           class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition flex items-center gap-2">
           <span>➕</span> Nouveau Badge
        </a>
    </div>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" id="searchInput" placeholder="Rechercher par membre, UID ou statut..."
           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block pl-10 p-2.5 shadow-sm transition duration-200">
    <svg xmlns="http://www.w3.org/2000/svg" 
         class="absolute left-3 top-3 text-gray-500 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
  </div>

  <!-- ===== TABLE ===== -->
  <div id="badgesTableContainer">
    @include('badges.partials.table', ['badges' => $badges])
  </div>

</div>

<!-- Alpine & SweetAlert -->


<script>
function badgeModal() {
  return {
    // Modal logic if needed
  }
}

// ===== AJAX Search + Pagination =====
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('searchInput');
  let searchTimeout = null;

  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => performSearch(), 400);
  });

  document.addEventListener('click', e => {
    const link = e.target.closest('.pagination a');
    if (link) {
      e.preventDefault();
      const page = new URL(link.href).searchParams.get('page') || 1;
      performSearch(page);
    }
  });
});

function performSearch(page = 1) {
  const query = document.getElementById('searchInput').value;
  fetch(`{{ route('badges.search') }}?q=${encodeURIComponent(query)}&page=${page}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(res => res.json())
    .then(data => {
      document.getElementById('badgesTableContainer').innerHTML = data.html;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    })
    .catch(err => console.error(err));
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Supprimer ce badge ?',
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
