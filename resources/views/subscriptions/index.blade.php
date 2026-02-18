@extends('layouts.app')
@section('title', 'Abonnements')

@section('content')
<div 
  x-data="{
    showModal: false,
    selected: {
      badge: { uid: '', status: '', color: '' },
      slots: [],
      payments: [],
      audit: { created_at: '', updated_at: '', created_by: '', updated_by: '' }
    },
    openModal(sub) {
      this.selected = Object.assign({
        badge: { uid: '', status: '', color: '' },
        slots: [],
        payments: [],
        audit: { created_at: '', updated_at: '', created_by: '', updated_by: '' }
      }, sub);

      this.showModal = true;
      this.activeTab = 'details';
    },
    closeModal() {
      this.showModal = false;
    },
    activeTab: 'details'
  }"
>

  <div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">📋 {{ $pageTitle ?? 'Liste des Abonnements' }}</h2>
    <a href="{{ isset($type) && $type === 'members' ? route('subscriptions.members.create') : (isset($type) && $type === 'groups' ? route('subscriptions.groups.create') : route('subscriptions.create')) }}"
       class="bg-blue-600 text-white px-5 py-2 rounded-lg shadow hover:bg-blue-700 transition">
       ➕ Nouvel abonnement
    </a>
  </div>

  <!-- Barre de recherche -->
  <div class="relative mb-6">
    <input type="text" id="searchInput" placeholder="Rechercher par membre, activité, plan ou statut..."
           class="w-full border border-gray-300 rounded-lg px-4 py-2 pl-10 focus:ring focus:ring-blue-100 focus:border-blue-400">
    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-2.5 text-gray-400 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1116.65 16.65z" />
    </svg>
    <div id="loadingSpinner" class="absolute right-3 top-2.5 hidden">
        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>
  </div>

  <!-- Tableau Container -->
  <div id="tableContainer" class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
      @include('subscriptions.partials.table', ['subscriptions' => $subscriptions])
  </div>

<style>
  [x-cloak] { display: none !important; }
</style>

  <!-- MODAL -->
  <div x-cloak x-show="showModal" x-transition 
       class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">

    <div class="bg-white rounded-xl shadow-xl w-[90%] md:w-[700px] p-6 relative"
         @click.away="closeModal()">

      <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
              @click="closeModal()">✕</button>

      <h3 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Détails de l’abonnement</h3>

      <!-- Tabs -->
      <div class="flex border-b mb-4 text-sm font-medium">
        <button :class="activeTab==='details' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-500'"
                class="px-3 py-2 transition-all duration-300 ease-in-out"
                @click="activeTab='details'">📋 Détails</button>

        <button :class="activeTab==='payments' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-500'"
                class="px-3 py-2 transition-all duration-300 ease-in-out"
                @click="activeTab='payments'">💳 Paiements</button>

        <button :class="activeTab==='slots' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-500'"
                class="px-3 py-2 transition-all duration-300 ease-in-out"
                @click="activeTab='slots'">🕒 Créneaux</button>

        @if (Auth::user()->role->role_name === 'admin')
        <button :class="activeTab==='audit' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-blue-500'"
                class="px-3 py-2 transition-all duration-300 ease-in-out"
                @click="activeTab='audit'">🧾 Audit</button>
        @endif
      </div>

      <!-- Details -->
      <div x-show="activeTab==='details'" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-2"
           x-transition:enter-end="opacity-100 translate-y-0"
           class="space-y-3 text-gray-700">
        <p><strong>👤 Membre :</strong> <span x-text="selected.member"></span></p>

        <p><strong>🎫 Badge :</strong>
          <span class="px-2 py-1 rounded text-xs font-semibold"
            :class="{
              'bg-green-100 text-green-700': selected.badge.color==='green',
              'bg-gray-100 text-gray-700': selected.badge.color==='gray',
              'bg-yellow-100 text-yellow-700': selected.badge.color==='yellow',
              'bg-red-100 text-red-700': selected.badge.color==='red',
            }">
            <span x-text="selected.badge.uid"></span> —
            <span x-text="selected.badge.status"></span>
          </span>
        </p>

        <p><strong>🏊 Activité :</strong> <span x-text="selected.activity"></span></p>
        <p><strong>🗓 Période :</strong> <span x-text="selected.start"></span> → <span x-text="selected.end"></span></p>
        <p><strong>📆 Visites / semaine :</strong> <span x-text="selected.visits"></span></p>
        <p><strong>📅 Jours autorisés :</strong> <span x-text="selected.days"></span></p>
      </div>

      <!-- SLOTS -->
      <div x-show="activeTab==='slots'" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-2"
           x-transition:enter-end="opacity-100 translate-y-0"
           class="space-y-4">
        <template x-if="selected.slots.length">
          <div class="space-y-3">
            <template x-for="slot in selected.slots">
              <div class="p-3 rounded-lg border bg-gray-50">
                <p><strong>📅 Jour :</strong> <span x-text="slot.day"></span></p>
                <p><strong>⏱ Horaire :</strong> 
                   <span x-text="slot.start"></span> →
                   <span x-text="slot.end"></span>
                </p>
                <p><strong>⌛ Durée :</strong> <span x-text="slot.duration"></span></p>
                <p><strong>🏊 Activité :</strong>
                  <span class="px-2 py-1 rounded text-white text-xs"
                        :style="'background:'+slot.activity_color">
                    <span x-text="slot.activity"></span>
                  </span>
                </p>
              </div>
            </template>
          </div>
        </template>

        <template x-if="!selected.slots.length">
          <p class="text-gray-500 text-center">Aucun créneau assigné.</p>
        </template>
      </div>

      <!-- Payments -->
      <div x-show="activeTab==='payments'" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-2"
           x-transition:enter-end="opacity-100 translate-y-0">
        @include('subscriptions.partials.payments-summary')
      </div>

      <!-- Audit -->
      @if (Auth::user()->role->role_name === 'admin')
      <div x-show="activeTab === 'audit'" 
           x-transition:enter="transition ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-2"
           x-transition:enter-end="opacity-100 translate-y-0"
           class="space-y-3 text-gray-700 text-sm">

  <p>
    <strong>Créé le :</strong>
    <span x-text="selected.audit ? selected.audit.created_at : '—'"></span>
  </p>

  <p>
    <strong>Créé par :</strong>
    <span x-text="selected.audit ? selected.audit.created_by : '—'"></span>
  </p>

  <p>
    <strong>Modifié le :</strong>
    <span x-text="selected.audit ? selected.audit.updated_at : '—'"></span>
  </p>

  <p>
    <strong>Modifié par :</strong>
    <span x-text="selected.audit ? selected.audit.updated_by : '—'"></span>
  </p>

</div>
      @endif

      <div class="mt-6 text-right">
        <button @click="closeModal()" class="px-5 py-2 rounded-lg border border-gray-300 hover:bg-gray-100">
          Fermer
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const tableContainer = document.getElementById('tableContainer');
        const loadingSpinner = document.getElementById('loadingSpinner');
        let debounceTimer;

        // Function to fetch data
        function fetchSubscriptions(url) {
            loadingSpinner.classList.remove('hidden');

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                tableContainer.innerHTML = html;
                // Re-initialize any JS listeners if needed (not needed for Alpine if x-data wraps it)
                // However, new DOM nodes inside x-data need to be recognized. 
                // Since x-data is on the parent <div>, basic bindings usually persist, 
                // but @click on new elements works via standard event bubbling mostly.
                // If specific initialization is needed, we could dispatch an event.
            })
            .catch(error => {
                console.error('Error fetching subscriptions:', error);
            })
            .finally(() => {
                loadingSpinner.classList.add('hidden');
            });
        }

        // Search Input Listener (Debounced)
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const query = this.value;
                const type = '{{ $type ?? "all" }}';
                const url = `{{ route('subscriptions.index') }}?search=${encodeURIComponent(query)}&type=${type}`;
                // Note: The route helper above points to 'subscriptions.index' which might strictly go to /subscriptions.
                // But we are in a specific page context (members/groups).
                // Actually, fetchSubscriptions should probably use window.location.pathname or valid route.
                // Let's use the current URL path but add search param.
                
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('search', query);
                // Type is implicitly handled by the route if we hit the same endpoint, 
                // BUT the controller uses route method name or just separate routes.
                // If we use window.location, we hit the correct endpoint (e.g. /subscriptions/members).
                
                fetchSubscriptions(currentUrl.toString());
            }, 300); // 300ms debounce
        });

        // Pagination Click Listener (Event Delegation)
        tableContainer.addEventListener('click', function(e) {
            const link = e.target.closest('.pagination a');
            if (link) {
                e.preventDefault();
                const url = link.getAttribute('href');
                // Ensure type/search params are preserved is handled by Laravel's withQueryString() in controller
                fetchSubscriptions(url);
            }
        });
    });

    function confirmDelete(subscriptionId) {
      Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette action supprimera définitivement l’abonnement.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
      }).then(result => {
        if (result.isConfirmed) {
          document.getElementById('delete-form-' + subscriptionId).submit();
        }
      });
    }
</script>

@endsection
