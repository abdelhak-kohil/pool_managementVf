@extends('layouts.app')
@section('title', 'Gestion des Coachs')

@section('content')
<div x-data="coachManager(@js($coaches))" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- Top Action Bar -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Équipe Coaching
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Gérez vos entraîneurs, leurs plannings et leurs performances.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('coaches.planning') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Planning
            </a>
            <a href="{{ route('coaches.reports.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Rapports
            </a>
             <a href="{{ route('coaches.attendance.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Pointage
            </a>
            <a href="{{ route('coaches.create') }}" class="inline-flex items-center px-5 py-2.5 bg-blue-600 border border-transparent rounded-xl shadow-lg shadow-blue-200 text-sm font-semibold text-white hover:bg-blue-700 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Nouveau Coach
            </a>
        </div>
    </div>

    <!-- Stats Overview (Optional, kept simple) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Coachs</p>
                <p class="text-2xl font-bold text-gray-900" x-text="coaches.length"></p>
            </div>
            <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Actifs</p>
                <p class="text-2xl font-bold text-gray-900" x-text="coaches.filter(c => c.is_active).length"></p>
            </div>
            <div class="p-3 bg-green-50 rounded-xl text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Filters & Controls -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col md:flex-row justify-between items-center gap-4 sticky top-20 z-10 transition-shadow hover:shadow-md">
        
        <!-- Search -->
        <div class="relative w-full md:w-96">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input 
                x-model="search" 
                type="text" 
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors duration-200" 
                placeholder="Rechercher par nom, email ou spécialité..." 
                autofocus
            >
        </div>

        <div class="flex items-center gap-3 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
            <!-- Filter Status -->
            <select x-model="filterStatus" class="block w-full md:w-auto pl-3 pr-10 py-2.5 text-base border-gray-200 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl bg-gray-50 text-gray-700">
                <option value="all">Tous les statuts</option>
                <option value="active">Actifs seulement</option>
                <option value="inactive">Inactifs</option>
            </select>

            <!-- View Toggles -->
            <div class="flex items-center p-1 bg-gray-100 rounded-lg">
                <button 
                    @click="view = 'grid'" 
                    :class="{'bg-white text-blue-600 shadow-sm': view === 'grid', 'text-gray-500 hover:text-gray-700': view !== 'grid'}"
                    class="p-2 rounded-md transition-all duration-200 focus:outline-none"
                    title="Vue Grille"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                </button>
                <button 
                    @click="view = 'list'" 
                    :class="{'bg-white text-blue-600 shadow-sm': view === 'list', 'text-gray-500 hover:text-gray-700': view !== 'list'}"
                    class="p-2 rounded-md transition-all duration-200 focus:outline-none"
                    title="Vue Liste"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- GRID VIEW -->
    <div x-show="view === 'grid'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="coach in filteredCoaches" :key="coach.staff_id">
            <div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 transition-all duration-300 transform hover:-translate-y-1 overflow-hidden flex flex-col">
                <!-- Card Header -->
                <div class="relative p-6 flex flex-col items-center border-b border-gray-50 flex-grow">
                    <div class="absolute top-4 right-4">
                        <span x-show="coach.is_active" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200 shadow-sm">
                            Actif
                        </span>
                        <span x-show="!coach.is_active" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                            Inactif
                        </span>
                    </div>

                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-3xl font-bold shadow-lg mb-4 transform group-hover:scale-110 transition-transform duration-300">
                        <span x-text="coach.avatar_initials"></span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-gray-900 text-center line-clamp-1" x-text="coach.full_name"></h3>
                    <p class="text-sm text-blue-600 font-medium mb-2" x-text="coach.specialty || 'Généraliste'"></p>
                    
                    <div class="w-full mt-4 grid grid-cols-2 gap-2 text-center">
                        <div class="bg-gray-50 rounded-lg p-2">
                             <p class="text-xs text-gray-400 uppercase font-semibold">Sessions</p>
                             <p class="text-lg font-bold text-gray-800" x-text="coach.session_count"></p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-2">
                             <p class="text-xs text-gray-400 uppercase font-semibold">Taux</p>
                             <p class="text-xs font-bold text-gray-800 mt-1" x-text="coach.salary_type === 'fixed' ? 'Fixe' : (coach.hourly_rate + '€/h')"></p>
                        </div>
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <div class="flex space-x-2">
                        <a :href="coach.video_url" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-full transition-colors" title="Envoyer un email" :href="'mailto:' + coach.email">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </a>
                        <a class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-full transition-colors" title="Appeler" :href="'tel:' + coach.phone_number">
                           <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2 3 3 0 003 3 2 2 0 012 2 3 3 0 003 3 2 2 0 012 2 3 3 0 012 2 13 13 0 01-13-13z"></path></svg>
                        </a>
                    </div>
                    <div class="flex space-x-1">
                        <a :href="coach.edit_url" class="text-xs font-medium px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-colors">Modifier</a>
                        <a :href="coach.view_url" class="text-xs font-medium px-3 py-1.5 bg-blue-600 border border-transparent rounded-lg text-white hover:bg-blue-700 transition-colors">Voir</a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- LIST VIEW -->
    <div x-show="view === 'list'" x-transition:enter="transition ease-out duration-200" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-blue-600" @click="sortBy('full_name')">
                            Coach <span x-show="sortCol === 'full_name'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider cursor-pointer hover:text-blue-600" @click="sortBy('specialty')">
                            Spécialité <span x-show="sortCol === 'specialty'" x-text="sortAsc ? '↑' : '↓'"></span>
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Contact</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Sessions</th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="coach in filteredCoaches" :key="coach.staff_id">
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                            <span x-text="coach.avatar_initials"></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-gray-900" x-text="coach.full_name"></div>
                                        <div class="text-xs text-gray-400">Dernier accès: <span x-text="coach.last_access_date || 'Jamais'"></span></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800" x-text="coach.specialty || 'Non spécifié'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="coach.email"></div>
                                <div class="text-xs text-gray-500" x-text="coach.phone_number"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <template x-if="coach.is_active">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span> Actif
                                    </span>
                                </template>
                                <template x-if="!coach.is_active">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                        Inactif
                                    </span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-gray-900" x-text="coach.session_count"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-3">
                                    <a :href="coach.view_url" class="text-gray-400 hover:text-blue-600 transition-colors" title="Voir">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>
                                    <a :href="coach.edit_url" class="text-gray-400 hover:text-amber-600 transition-colors" title="Modifier">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </a>
                                    <button @click="confirmDelete(coach)" class="text-gray-400 hover:text-red-600 transition-colors" title="Supprimer">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Empty State -->
    <div x-show="filteredCoaches.length === 0" class="text-center py-16" x-cloak>
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900">Aucun coach trouvé</h3>
        <p class="mt-1 text-gray-500">Essayez de modifier vos termes de recherche ou vos filtres.</p>
        <button @click="search = ''; filterStatus = 'all'" class="mt-4 text-blue-600 hover:text-blue-700 font-medium">Réinitialiser les filtres</button>
    </div>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" method="POST" action="">
        @csrf
        @method('DELETE')
    </form>

</div>

<script>
    function coachManager(coachesData) {
        return {
            coaches: coachesData,
            search: '',
            filterStatus: 'all', // all, active, inactive
            view: 'grid', // grid, list
            sortCol: 'full_name',
            sortAsc: true,

            get filteredCoaches() {
                let result = this.coaches;

                // Status Filter
                if (this.filterStatus === 'active') {
                    result = result.filter(c => c.is_active);
                } else if (this.filterStatus === 'inactive') {
                    result = result.filter(c => !c.is_active);
                }

                // Search Filter
                const q = this.search.toLowerCase();
                if (q) {
                    result = result.filter(c => 
                        (c.full_name && c.full_name.toLowerCase().includes(q)) || 
                        (c.email && c.email.toLowerCase().includes(q)) || 
                        (c.specialty && c.specialty.toLowerCase().includes(q))
                    );
                }

                // Sort
                result = result.sort((a, b) => {
                    let valA = a[this.sortCol] ? a[this.sortCol].toString().toLowerCase() : '';
                    let valB = b[this.sortCol] ? b[this.sortCol].toString().toLowerCase() : '';
                    if (valA < valB) return this.sortAsc ? -1 : 1;
                    if (valA > valB) return this.sortAsc ? 1 : -1;
                    return 0;
                });

                return result;
            },

            sortBy(col) {
                if (this.sortCol === col) {
                    this.sortAsc = !this.sortAsc;
                } else {
                    this.sortCol = col;
                    this.sortAsc = true;
                }
            },

            confirmDelete(coach) {
                Swal.fire({
                    title: 'Supprimer ' + coach.full_name + ' ?',
                    text: "Cette action est irréversible. Le coach sera supprimé s'il n'a pas de créneaux assignés.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, supprimer',
                    cancelButtonText: 'Annuler'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('deleteForm');
                        form.action = coach.destroy_url;
                        form.submit();
                    }
                });
            }
        };
    }
</script>
@endsection
