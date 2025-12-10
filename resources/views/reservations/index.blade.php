@extends('layouts.app')
@section('title', 'Gestion des Réservations')

@section('content')
<div x-data="reservationsManager()" class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- HEADER -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Réservations
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Planifiez et gérez les créneaux pour les membres et les groupes.</p>
        </div>
        <div>
            <a href="{{ route('reservations.create') }}" 
               class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 border border-transparent rounded-xl shadow-lg text-sm font-semibold text-white hover:from-blue-700 hover:to-indigo-700 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nouvelle Réservation
            </a>
        </div>
    </div>

    <!-- STATS SUMMARY (Optional but nice) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400">Total Réservations</p>
                <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $reservations->total() }}</p>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        </div>
        <!-- Add more stats here if backend provides them later -->
    </div>

    <!-- MAIN CONTENT CARD -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        
        <!-- SEARCH BAR -->
        <div class="p-5 border-b border-gray-100 bg-gray-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="relative w-full sm:w-96">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" 
                       id="searchInput" 
                       class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out" 
                       placeholder="Rechercher (Membre, Groupe, ID)...">
            </div>
            <!-- Pagination Info or Filters could go here -->
        </div>

        <!-- TABLE CONTAINER -->
        <div id="reservationsTableContainer" class="relative">
            @include('reservations.partials.table', ['reservations' => $reservations])
        </div>
    </div>
</div>

<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reservationsManager', () => ({
            // Logic for future modals or bulk actions
        }));
    });

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    @if(session('success'))
        Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
    @endif
    @if(session('error'))
        Toast.fire({ icon: 'error', title: "{{ session('error') }}" });
    @endif

    // ===== AJAX Search =====
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
        const container = document.getElementById('reservationsTableContainer');
        
        // Simple opacity loader
        container.style.opacity = '0.5';
        
        fetch(`{{ route('reservations.search') }}?q=${encodeURIComponent(query)}&page=${page}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            container.innerHTML = data.html;
            container.style.opacity = '1';
        })
        .catch(err => {
            console.error(err);
            container.style.opacity = '1';
            Toast.fire({ icon: 'error', title: 'Erreur lors de la recherche' });
        });
    }

    // ===== Delete Confirmation =====
    function confirmDelete(formId) {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: "Cette réservation sera définitivement supprimée.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#e5e7eb',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: '<span class="text-gray-700">Annuler</span>',
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'rounded-xl px-4 py-2',
                cancelButton: 'rounded-xl px-4 py-2'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>
@endsection
