@extends('layouts.app')
@section('title', 'Nouveau Coach')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Ajouter un Coach</h1>
            <p class="mt-2 text-sm text-gray-600">Remplissez le formulaire ci-dessous pour intégrer un nouveau membre à l'équipe.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('coaches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour à la liste
            </a>
        </div>
    </div>

    <form action="{{ route('coaches.store') }}" method="POST" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Personal & Professional Info -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Personal Information Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                        <div class="p-2 bg-blue-100 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Informations Personnelles</h3>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required 
                                class="w-full h-11 px-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required 
                                class="w-full h-11 px-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                    </svg>
                                </div>
                                <input type="email" name="email" value="{{ old('email') }}" 
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow" placeholder="coach@example.com">
                            </div>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <input type="text" name="phone_number" value="{{ old('phone_number') }}" 
                                    class="w-full h-11 pl-10 pr-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow" placeholder="0555...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professional Information Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                        <div class="p-2 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Expertise & Badge</h3>
                    </div>
                    
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                            <input type="text" name="specialty" value="{{ old('specialty') }}" 
                                class="w-full h-11 px-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow" placeholder="Ex: Natation, Fitness...">
                        </div>
                        <div class="col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche</label>
                            <input type="date" name="hiring_date" value="{{ old('hiring_date', date('Y-m-d')) }}" 
                                class="w-full h-11 px-4 rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow">
                        </div>
                        <div class="col-span-full">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Badge UID</label>
                            
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                        Badges Disponibles ({{ isset($availableBadges) ? $availableBadges->count() : 0 }})
                                    </label>
                                    <select id="badge_select" class="w-full h-11 px-4 rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500 transition-shadow bg-white text-gray-700 disabled:bg-gray-100 disabled:text-gray-400"
                                        {{ (!isset($availableBadges) || $availableBadges->isEmpty()) ? 'disabled' : '' }}>
                                        
                                        @if(isset($availableBadges) && $availableBadges->count() > 0)
                                            <option value="">— Choisir un badge à assigner —</option>
                                            @foreach($availableBadges as $badge)
                                                <option value="{{ $badge->badge_uid }}">Badge #{{ $badge->badge_uid }}</option>
                                            @endforeach
                                        @else
                                            <option value="">Aucun badge disponible (créez-en un ci-dessous)</option>
                                        @endif
                                    </select>
                                </div>
                                
                                <div class="relative flex items-center">
                                    <div class="flex-grow border-t border-gray-200"></div>
                                    <span class="flex-shrink-0 mx-4 text-gray-400 text-sm">OU CRÉER UN NOUVEAU</span>
                                    <div class="flex-grow border-t border-gray-200"></div>
                                </div>

                                <div class="flex gap-2">
                                    <div class="relative flex-grow">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                            </svg>
                                        </div>
                                        <input type="text" id="badge_uid" name="badge_uid" value="{{ old('badge_uid') }}" 
                                            class="w-full h-11 pl-10 pr-4 rounded-xl border-gray-300 focus:border-purple-500 focus:ring-purple-500 transition-shadow font-mono" placeholder="Saisir ou scanner l'ID du badge">
                                    </div>
                                    <button type="button" class="px-4 py-2 bg-purple-50 text-purple-700 rounded-xl hover:bg-purple-100 transition-colors border border-purple-200">
                                        Scanner
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500">Un nouveau badge sera créé automatiquement si l'ID n'existe pas.</p>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const badgeSelect = document.getElementById('badge_select');
                                const badgeInput = document.getElementById('badge_uid');
                                
                                if(badgeSelect && badgeInput) {
                                    badgeSelect.addEventListener('change', function() {
                                        const selectedValue = this.value;
                                        if(selectedValue) {
                                            badgeInput.value = selectedValue;
                                            // UI Feedback
                                            badgeInput.classList.add('bg-purple-50');
                                            setTimeout(() => badgeInput.classList.remove('bg-purple-50'), 300);
                                        } else {
                                            badgeInput.value = '';
                                        }
                                    });
                                } else {
                                    console.error('Badge selection elements not found');
                                }
                            });
                        </script>
                    </div>
                </div>

                <!-- Notes Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center">
                        <div class="p-2 bg-yellow-100 rounded-lg mr-3">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900">Notes & Remarques</h3>
                    </div>
                    <div class="p-6">
                        <textarea name="notes" rows="4" 
                            class="w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500 transition-shadow" placeholder="Information supplémentaire concernant ce coach...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Right Column: Financial Info & Actions -->
            <div class="lg:col-span-1 space-y-8">
                
                <!-- Financial Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden sticky top-8">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50 flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg mr-3 shadow-sm">
                            <svg class="w-5 h-5 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-green-900">Contrat</h3>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de Salaire</label>
                            <div class="relative">
                                <select name="salary_type" class="w-full h-11 pl-4 pr-10 rounded-xl border-gray-300 focus:border-green-500 focus:ring-green-500 transition-shadow appearance-none bg-none">
                                    <option value="per_hour" {{ old('salary_type') == 'per_hour' ? 'selected' : '' }}>Par Heure</option>
                                    <option value="per_session" {{ old('salary_type') == 'per_session' ? 'selected' : '' }}>Par Séance</option>
                                    <option value="fixed" {{ old('salary_type') == 'fixed' ? 'selected' : '' }}>Fixe (Mensuel)</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tarif / Montant</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 font-bold">DZD</span>
                                </div>
                                <input type="number" step="0.01" name="hourly_rate" value="{{ old('hourly_rate', 0) }}" required 
                                    class="w-full h-11 pl-12 pr-4 rounded-xl border-gray-300 focus:border-green-500 focus:ring-green-500 transition-shadow font-mono text-lg font-semibold text-gray-800">
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-100">
                             <button type="submit" class="w-full flex items-center justify-center px-6 py-4 border border-transparent rounded-xl shadow-md text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.02]">
                                <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Enregistrer le Coach
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection
