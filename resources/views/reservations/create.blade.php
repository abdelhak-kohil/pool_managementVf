@extends('layouts.app')
@section('title', 'Nouvelle Réservation')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Nouvelle Réservation</h1>
            <nav class="flex text-sm text-gray-500 mt-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 transition-colors">Accueil</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><a href="{{ route('reservations.index') }}" class="hover:text-blue-600 transition-colors">Réservations</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li class="text-gray-900 font-medium">Créer</li>
                </ol>
            </nav>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('reservations.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
        </div>
    </div>

    @php
        $hasVideActivity = \Illuminate\Support\Facades\DB::table('pool_schema.activities')->where('name', 'VIDE')->exists();
    @endphp

    @if(!$hasVideActivity)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-bold text-yellow-800">
                        Information : L'activité "VIDE" n'est pas configurée dans la base de données.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('reservations.store') }}" method="POST" class="block" x-data="reservationForm()">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Slots Selection -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Disponibilité</h2>
                            <p class="text-sm text-gray-500 mt-0.5">Sélectionnez un créneau horaire pour la réservation</p>
                        </div>
                        <div class="h-8 w-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <input type="hidden" name="slot_id" x-model="selectedSlotId" required>

                        @if($availableSlots->isEmpty())
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="h-12 w-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Aucun créneau disponible</h3>
                                <p class="text-gray-500 mt-1 max-w-sm">Il n'y a pas de créneaux disponibles pour le moment. Veuillez vérifier ultérieurement ou changer de date.</p>
                            </div>
                        @else
                            <div class="space-y-8">
                                @foreach($availableSlots->groupBy('day_name') as $day => $slots)
                                    <div>
                                        <div class="flex items-center gap-2 mb-4">
                                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                                            <h3 class="text-md font-bold text-gray-800 capitalize">{{ $day }}</h3>
                                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-600 border border-gray-200">{{ $slots->count() }} créneaux</span>
                                        </div>
                                        
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                            @foreach($slots as $slot)
                                                <div @click="selectSlot({{ $slot->slot_id }})"
                                                     class="group relative cursor-pointer rounded-xl border p-4 text-center transition-all duration-200 hover:shadow-md"
                                                     :class="selectedSlotId == {{ $slot->slot_id }} 
                                                        ? 'bg-blue-600 border-blue-600 ring-2 ring-blue-200 ring-offset-1 text-white shadow-lg transform scale-[1.02]' 
                                                        : 'bg-white border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 text-gray-700'">
                                                    
                                                    <div class="flex items-center justify-center gap-1.5 mb-1">
                                                        <span class="font-bold text-lg"
                                                              :class="selectedSlotId == {{ $slot->slot_id }} ? 'text-white' : 'text-gray-900'">
                                                            {{ substr($slot->start_time, 0, 5) }}
                                                        </span>
                                                    </div>
                                                    
                                                    <div class="text-xs font-medium opacity-80"
                                                         :class="selectedSlotId == {{ $slot->slot_id }} ? 'text-blue-100' : 'text-gray-500'">
                                                        Jusqu'à {{ substr($slot->end_time, 0, 5) }}
                                                    </div>

                                                    <!-- Selected Checkmark -->
                                                    <div x-show="selectedSlotId == {{ $slot->slot_id }}" 
                                                         x-transition:enter="transition ease-out duration-200"
                                                         x-transition:enter-start="opacity-0 scale-75"
                                                         x-transition:enter-end="opacity-100 scale-100"
                                                         class="absolute -top-2 -right-2 h-6 w-6 bg-white rounded-full text-blue-600 shadow-sm flex items-center justify-center border border-blue-100">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Details -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 sticky top-6">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-lg font-semibold text-gray-900">Détails</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Informations du client</p>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Reservation Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de réservation</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                                    </svg>
                                </div>
                                <select name="reservation_type" x-model="type" required
                                        class="block w-full pl-10 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-gray-50/50 transition-colors hover:bg-white">
                                    <option value="">— Choisir le type —</option>
                                    <option value="member_private">Individuelle (Membre)</option>
                                    <option value="partner_group">Groupe Partenaire</option>
                                </select>
                            </div>
                        </div>

                        <!-- Member Selection -->
                        <div x-show="type === 'member_private'" x-bg-transition
                             x-data="searchableSelect('{{ route('reservations.searchMembers') }}', 'member_id')">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Membre</label>
                            <div class="relative">
                                <input type="hidden" name="member_id" :value="selectedId">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="search" @input.debounce.300ms="fetchResults()"
                                           @click="open = true" @click.outside="open = false"
                                           placeholder="Rechercher un membre..."
                                           class="block w-full pl-10 pr-10 py-2.5 text-base border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-gray-50/50">
                                    
                                    <button type="button" x-show="selectedName" @click="clearSelection()" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Dropdown Results -->
                                <div x-show="open && (results.length > 0 || loading)" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     class="absolute z-20 mt-1 w-full bg-white shadow-xl rounded-lg border border-gray-100 max-h-60 overflow-y-auto">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li x-show="loading" class="px-4 py-3 text-gray-500 flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Recherche en cours...
                                        </li>
                                        <template x-for="item in results" :key="item.id">
                                            <li @click="selectItem(item)"
                                                class="group px-4 py-2.5 hover:bg-blue-50 cursor-pointer flex items-center gap-2">
                                                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <span x-text="item.name.charAt(0)"></span>
                                                </div>
                                                <span x-text="item.name" class="font-medium text-gray-900 group-hover:text-blue-700"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Group Selection -->
                        <div x-show="type === 'partner_group'" x-bg-transition
                             x-data="searchableSelect('{{ route('reservations.searchGroups') }}', 'partner_group_id')">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Groupe Partenaire</label>
                            <div class="relative">
                                <input type="hidden" name="partner_group_id" :value="selectedId">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <input type="text" x-model="search" @input.debounce.300ms="fetchResults()"
                                           @click="open = true" @click.outside="open = false"
                                           placeholder="Rechercher un groupe..."
                                           class="block w-full pl-10 pr-10 py-2.5 text-base border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-gray-50/50">
                                            
                                    <button type="button" x-show="selectedName" @click="clearSelection()" 
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 cursor-pointer">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Dropdown Results -->
                                <div x-show="open && (results.length > 0 || loading)" 
                                     x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     class="absolute z-20 mt-1 w-full bg-white shadow-xl rounded-lg border border-gray-100 max-h-60 overflow-y-auto">
                                    <ul class="py-1 text-sm text-gray-700">
                                        <li x-show="loading" class="px-4 py-3 text-gray-500 flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Recherche en cours...
                                        </li>
                                        <template x-for="item in results" :key="item.id">
                                            <li @click="selectItem(item)"
                                                class="group px-4 py-2.5 hover:bg-blue-50 cursor-pointer flex items-center gap-2">
                                                <div class="h-8 w-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                </div>
                                                <span x-text="item.name" class="font-medium text-gray-900 group-hover:text-blue-700"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes & Remarques</label>
                            <div class="relative">
                                <textarea name="notes" rows="4" 
                                          class="block w-full text-base border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-gray-50/50"
                                          placeholder="Optionnel : détails supplémentaires..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex flex-col gap-3">
                        <button type="submit" 
                                :disabled="!isFormValid()"
                                class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            Confirmer la Réservation
                        </button>
                        <a href="{{ route('reservations.index') }}" class="w-full inline-flex justify-center items-center px-4 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reservationForm', () => ({
            selectedSlotId: '',
            type: '',

            selectSlot(id) {
                this.selectedSlotId = id;
            },

            isFormValid() {
                if (!this.selectedSlotId) return false;
                if (!this.type) return false;
                // Add more complex validation if needed (e.g., waiting for member selection)
                return true;
            }
        }));

        Alpine.data('searchableSelect', (url, fieldName) => ({
            search: '',
            selectedId: '',
            selectedName: '',
            open: false,
            results: [],
            loading: false,

            fetchResults() {
                if (this.search.length < 2) {
                    this.results = [];
                    return;
                }

                this.loading = true;
                fetch(`${url}?q=${this.search}`)
                    .then(res => res.json())
                    .then(data => {
                        this.results = data.map(item => ({
                            id: item.member_id || item.group_id,
                            name: item.name
                        }));
                        this.loading = false;
                    })
                    .catch(() => {
                        this.loading = false;
                    });
            },

            selectItem(item) {
                this.selectedId = item.id;
                this.selectedName = item.name;
                this.search = item.name;
                this.open = false;
            },

            clearSelection() {
                this.selectedId = '';
                this.selectedName = '';
                this.search = '';
                this.results = [];
            }
        }));
    });
</script>
@endpush
@endsection
