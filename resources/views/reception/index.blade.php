@extends('layouts.app')
@section('title', 'Réception')

@section('content')
<div 
  x-data="receptionDashboard()" 
  x-init="initDashboard()"
  class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up"
>
  <!-- === Header === -->
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
    <div>
        <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
            <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                Tableau de Bord Réception
            </span>
        </h1>
        <p class="mt-2 text-sm text-gray-500">Gestion des accès, check-in rapide et suivi des membres.</p>
    </div>
    
    <div class="flex flex-wrap gap-3">
       @if (Auth::user()->role->role_name === 'admin' || Auth::user()->role->role_name === 'Admin')
      <a href="{{ route('attendance.dashboard') }}" class="group relative inline-flex items-center px-5 py-2.5 overflow-hidden rounded-xl bg-white border border-gray-200 text-sm font-medium text-gray-600 hover:text-blue-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-sm">
        <span class="absolute left-0 w-1 h-full bg-blue-500 transition-all group-hover:w-full opacity-10 group-hover:opacity-10"></span>
        <span class="relative flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Statistiques
        </span>
      </a>
      @endif

      <a href="{{ route('reception.scan') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-semibold text-white hover:from-blue-700 hover:to-indigo-700 hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
        <svg class="w-5 h-5 mr-2 -ml-1 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
        Mode Scan
      </a>
    </div>
  </div>

  <!-- === Stats Cards === -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Access Granted -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow group">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-green-600 transition-colors">Accès Autorisés</p>
        <p class="text-4xl font-extrabold text-gray-900 mt-2" x-text="stats.granted">0</p>
      </div>
      <div class="w-14 h-14 rounded-2xl bg-green-50 flex items-center justify-center text-green-500 group-hover:scale-110 transition-transform duration-300 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
    </div>

    <!-- Access Denied -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow group">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-red-600 transition-colors">Accès Refusés</p>
        <p class="text-4xl font-extrabold text-gray-900 mt-2" x-text="stats.denied">0</p>
      </div>
      <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition-transform duration-300 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
      </div>
    </div>

    <!-- Total Accesses -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between hover:shadow-md transition-shadow group">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 group-hover:text-blue-600 transition-colors">Total Passages</p>
        <p class="text-4xl font-extrabold text-gray-900 mt-2" x-text="stats.total">0</p>
      </div>
      <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform duration-300 shadow-sm">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      </div>
    </div>
  </div>
  
  <!-- === Search Bar === -->
  <div class="relative max-w-2xl mx-auto mb-8">
    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
       <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
       </svg>
    </div>
    <input 
      type="text" 
      id="searchInput"
      placeholder="Rechercher un membre (Nom, Badge, Téléphone)..."
      class="block w-full pl-12 pr-4 py-4 bg-white border border-gray-200 rounded-2xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm text-lg"
    >
    <div class="absolute inset-y-0 right-0 pr-4 flex items-center">
        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-md border border-gray-200">ESC pour effacer</span>
    </div>
  </div>

  <!-- === Members Table Container === -->
  <div id="membersTableContainer" class="transition-opacity duration-300">
    @include('reception.partials.members-table', ['members' => $members])
  </div>

</div>

<!-- === SweetAlert === -->
<script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('receptionDashboard', () => ({
    stats: { granted: 0, denied: 0, total: 0, active: 0 },
    showMemberModal: false,
    selectedMember: { audit: {}, subscriptions: [] },
    memberLogs: [],
    activeTab: 'details',

    initDashboard() {
      this.fetchStats();
      setInterval(() => this.fetchStats(), 10000);

      // make openMemberModal global so the AJAX table can access it
      window.openMemberModal = (member) => this.openMemberModal(member);
    },

    fetchStats() {
      fetch('{{ route("reception.today.accesses") }}')
        .then(res => res.json())
        .then(data => this.stats = data)
        .catch(err => console.error('Stats fetch error:', err));
    },

    openMemberModal(member) {
      this.selectedMember = member;
      this.activeTab = 'details';
      this.memberLogs = [];
      this.showMemberModal = true;
    },

    closeMemberModal() {
      this.showMemberModal = false;
      this.selectedMember = { audit: {}, subscriptions: [] };
      this.memberLogs = [];
    },

    fetchMemberLogs(memberId) {
      this.memberLogs = []; // Reset visual
      fetch(`/admin/reception/member/${memberId}/logs`)
        .then(res => res.json())
        .then(data => this.memberLogs = data)
        .catch(err => console.error('Logs fetch error:', err));
    },

    checkIn(memberId) {
      Swal.showLoading();
      fetch(`/admin/reception/checkin/${memberId}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
      })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Accès Autorisé',
                text: data.message,
                timer: 2000,
                showConfirmButton: false,
                backdrop: `rgba(0,0,123,0.1)`
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Accès Refusé',
                text: data.message,
                footer: data.reason ? `Raison: ${data.reason}` : null
            });
        }
        this.fetchStats();
      })
      .catch(err => {
          Swal.fire('Erreur', 'Impossible de valider l\'entrée.', 'error');
      });
    }
  }));
});

// === Pure JS Live Search + Pagination ===
document.addEventListener('DOMContentLoaded', () => {
  const container = document.getElementById('membersTableContainer');
  const searchInput = document.getElementById('searchInput');
  let searchTimeout;

  // Clear on ESC
  document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape' && document.activeElement === searchInput) {
          searchInput.value = '';
          fetchMembers();
      }
  });

  function bindPaginationLinks() {
    container.querySelectorAll('.pagination a').forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const url = new URL(link.href);
        const page = url.searchParams.get('page') || 1;
        fetchMembers(page);
      });
    });
  }

  function fetchMembers(page = 1) {
    const query = searchInput.value.trim();
    
    // Simple visual feedback
    container.classList.add('opacity-50');
    
    fetch(`{{ route('reception.search') }}?q=${encodeURIComponent(query)}&page=${page}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
      container.innerHTML = data.html;
      container.classList.remove('opacity-50');
      bindPaginationLinks();
    })
    .catch(err => {
        console.error('Fetch error:', err);
        container.classList.remove('opacity-50');
    });
  }

  searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchMembers(), 300);
  });

  bindPaginationLinks();
});
</script>

<style>
  [x-cloak] { display: none !important; }
  /* Smooth transitions */
  .fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
  .fade-enter, .fade-leave-to { opacity: 0; }
</style>
@endsection
