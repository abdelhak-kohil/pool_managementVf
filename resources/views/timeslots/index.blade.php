@extends('layouts.app')
@section('title','Créneaux horaires')

@section('content')
<div x-data="timeslotsTable()">
  <!-- ===== HEADER ===== -->
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">🗓️ Créneaux horaires</h2>
    <a href="{{ route('timeslots.create') }}"
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Ajouter un créneau
    </a>
  </div>

  <!-- ===== SEARCH BAR ===== -->
  <div class="relative mb-6">
    <input type="text" x-model="search" @input.debounce.500ms="fetchResults" placeholder="Rechercher un créneau (jour, activité, groupe...)"
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

  <!-- ===== TABLE CONTAINER ===== -->
  <div id="table-container">
      @include('timeslots.partials.table')
  </div>
</div>

<!-- Alpine & SweetAlert -->


<script>
function timeslotsTable() {
    return {
        search: '',
        fetchResults() {
            fetch(`{{ route('timeslots.index') }}?search=${this.search}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('table-container').innerHTML = html;
            });
        }
    }
}

// ===== SweetAlert Delete Confirmation =====
document.addEventListener('click', e => {
  if (e.target.classList.contains('delete-btn')) {
    e.preventDefault();
    const form = e.target.closest('form');
    Swal.fire({
      title: 'Supprimer ce créneau ?',
      text: "Cette action est irréversible.",
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

// Handle pagination clicks via AJAX
document.addEventListener('click', function(e) {
    const link = e.target.closest('#table-container nav[role="navigation"] a');
    if (link) {
        e.preventDefault();
        let url = link.href;
        
        // Append search param if exists
        const searchValue = document.querySelector('input[x-model="search"]').value;
        
        if (searchValue) {
            const separator = url.includes('?') ? '&' : '?';
            // Check if search is already in URL to avoid duplication (though simple append works usually, cleaner to check)
            if (!url.includes('search=')) {
                url = `${url}${separator}search=${encodeURIComponent(searchValue)}`;
            }
        }

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('table-container').innerHTML = html;
            window.history.pushState({}, '', url);
        });
    }
});
</script>
@endsection
