@extends('layouts.app')
@section('title', 'Ajouter un membre')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Nouveau Membre
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Ajouter un nouveau membre au personnel ou un coach.</p>
        </div>
        <a href="{{ route('staff.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
            <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour à la liste
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-xl shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Des erreurs ont été trouvées :</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('staff.store') }}" method="POST" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Column 1: Identity & Professional (2 cols wide) -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Identity Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="px-6 py-5 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                            <div class="p-2 bg-blue-100 rounded-lg text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            Identité & Compte
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name"
                                   class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                                   class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nom d'utilisateur <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-400">@</span>
                                </div>
                                <input type="text" name="username" value="{{ old('username') }}" required autocomplete="username"
                                       class="block w-full pl-8 rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Rôle <span class="text-red-500">*</span></label>
                            <select name="role_id" required
                                    class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5 bg-gray-50">
                                <option value="">-- Sélectionner un rôle --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->role_id }}" {{ old('role_id') == $r->role_id ? 'selected' : '' }}>
                                        {{ ucfirst($r->role_name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe <span class="text-red-500">*</span></label>
                            <input type="password" name="password" required autocomplete="new-password"
                                   class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmer le mot de passe <span class="text-red-500">*</span></label>
                            <input type="password" name="password_confirmation" required
                                   class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-2.5">
                        </div>
                    </div>
                </div>

                <!-- Professional Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="px-6 py-5 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                             <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                            Informations Professionnelles
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="exemple@domain.com"
                                       class="block w-full pl-10 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2 3 3 0 003 3 2 2 0 012 2 3 3 0 003 3 2 2 0 012 2 3 3 0 012 2 13 13 0 01-13-13z"></path></svg>
                                </div>
                                <input type="tel" name="phone_number" value="{{ old('phone_number') }}" autocomplete="tel" placeholder="05 XX XX XX XX"
                                       class="block w-full pl-10 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm py-2.5">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Spécialité</label>
                            <input type="text" name="specialty" value="{{ old('specialty') }}" placeholder="Ex: Natation, Yoga..."
                                   class="block w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm py-2.5">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date d'embauche</label>
                            <input type="date" name="hiring_date" value="{{ old('hiring_date') }}"
                                   class="block w-full rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 transition shadow-sm py-2.5">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Settings & Compensation (1 col wide) -->
            <div class="space-y-8">
                <!-- Access & Status Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="px-6 py-5 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                             <div class="p-2 bg-emerald-100 rounded-lg text-emerald-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 19.464a3 3 0 01-.879.879l-2.121 2.121h-2.828l-3.172-3.172v-2.828l2.121-2.121a3 3 0 01.879-.879l4.728-4.728A6 6 0 1115 7z"></path></svg>
                            </div>
                            Accès & Statut
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-100">
                            <div>
                                <label class="text-sm font-bold text-gray-900">Compte Actif</label>
                                <p class="text-xs text-gray-500 mt-0.5">Autoriser la connexion</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Badge UID</label>
                            @if($availableBadges->count() > 0)
                                <div class="relative">
                                     <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    </div>
                                    <select name="badge_uid" class="block w-full pl-10 rounded-xl border-gray-200 focus:border-emerald-500 focus:ring-emerald-500 transition shadow-sm py-2.5 bg-gray-50">
                                        <option value="">-- Sélectionner un badge disponible --</option>
                                        @foreach($availableBadges as $badge)
                                            <option value="{{ $badge->badge_uid }}">{{ $badge->badge_uid }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Sélectionnez parmi {{ $availableBadges->count() }} badges disponibles.</p>
                            @else
                                <div class="p-3 bg-yellow-50 text-yellow-700 text-sm rounded-lg flex items-start gap-2">
                                     <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                     <p>Aucun badge disponible. Veuillez en ajouter dans la section Gestion des Badges.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Compensation Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="px-6 py-5 border-b border-gray-50 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                             <div class="p-2 bg-amber-100 rounded-lg text-amber-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            Rémunération
                        </h3>
                    </div>
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Type de salaire</label>
                            <select name="salary_type" class="block w-full rounded-xl border-gray-200 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm py-2.5 bg-gray-50">
                                <option value="">-- Sélectionner --</option>
                                <option value="hourly" {{ old('salary_type') == 'hourly' ? 'selected' : '' }}>Taux Horaire</option>
                                <option value="monthly" {{ old('salary_type') == 'monthly' ? 'selected' : '' }}>Salaire Fixe Mensuel</option>
                                <option value="per_session" {{ old('salary_type') == 'per_session' ? 'selected' : '' }}>Par Séance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Montant (Taux/Salaire)</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="hourly_rate" value="{{ old('hourly_rate') }}" placeholder="0.00"
                                       class="block w-full pr-14 rounded-xl border-gray-200 focus:border-amber-500 focus:ring-amber-500 transition shadow-sm py-2.5 font-mono">
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none bg-gray-50 rounded-r-xl border-l border-gray-200 px-3">
                                    <span class="text-gray-500 text-sm font-medium">DZD</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Width: Notes -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <div class="px-6 py-4 border-b border-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">Notes & Remarques</h3>
                    </div>
                    <div class="p-6">
                        <textarea name="notes" rows="3" class="block w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 transition shadow-sm py-3" placeholder="Informations complémentaires, observations...">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200 mt-8">
            <a href="{{ route('staff.index') }}" class="px-6 py-3 bg-white border border-gray-200 text-gray-700 font-medium rounded-xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-medium rounded-xl hover:shadow-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition flex items-center shadow-md transform hover:-translate-y-0.5">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                Enregistrer le membre
            </button>
        </div>
    </form>
</div>
@endsection
