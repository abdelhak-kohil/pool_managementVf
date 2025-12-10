@extends('layouts.app')
@section('title', 'Gestion des Membres')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Gestion des Membres
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Administrez les comptes membres, abonnements et accès.</p>
        </div>
        <a href="{{ route('members.create') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-semibold text-white hover:bg-blue-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
            <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            Nouveau Membre
        </a>
    </div>

    <!-- SEARCH & FILTERS -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-1 mb-8">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                type="text" 
                id="searchInput" 
                class="block w-full pl-12 pr-4 py-3.5 bg-transparent border-none text-gray-900 placeholder-gray-400 focus:ring-0 text-base"
                placeholder="Rechercher un membre par nom, email, téléphone ou badge..."
                autocomplete="off"
            >
            <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                <span class="text-xs text-gray-400 bg-gray-50 border border-gray-100 px-2 py-1 rounded">Recherche instantanée</span>
            </div>
        </div>
    </div>

    <!-- TABLE CONTAINER -->
    <div id="membersTableContainer" class="transition-opacity duration-300">
        @include('members.partials.table', ['members' => $members])
    </div>

</div>

<!-- SCRIPTS -->
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('searchInput');
        const container = document.getElementById('membersTableContainer');
        let searchTimeout;

        // Search Logic
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            container.classList.add('opacity-60');
            searchTimeout = setTimeout(() => performSearch(), 400);
        });

        // Pagination Click Handling
        document.addEventListener('click', e => {
            const link = e.target.closest('.pagination a');
            if (link && container.contains(link)) {
                e.preventDefault();
                const page = new URL(link.href).searchParams.get('page') || 1;
                performSearch(page);
            }
        });

        function performSearch(page = 1) {
            const query = searchInput.value;
            fetch(`{{ route('members.search') }}?q=${encodeURIComponent(query)}&page=${page}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                container.innerHTML = data.html;
                container.classList.remove('opacity-60');
            })
            .catch(err => {
                console.error(err);
                container.classList.remove('opacity-60');
            });
        }

        // Delete Confirmation
        document.addEventListener('click', e => {
            if (e.target.matches('.delete-btn')) {
                const form = e.target.closest('form');
                if(!form) return;
                
                e.preventDefault();
                Swal.fire({
                    title: 'Êtes-vous sûr ?',
                    text: 'Cette suppression est définitive.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#e5e7eb',
                    customClass: {
                        cancelButton: 'text-gray-700'
                    }
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            }
        });
    });
</script>
@endsection
