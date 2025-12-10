@extends('layouts.app')
@section('title', 'Modifier un membre du personnel')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Modifier le membre</h2>
            <p class="text-sm text-gray-500">Mise à jour des informations pour {{ $staff->full_name }}</p>
        </div>
        <a href="{{ route('staff.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Des erreurs ont été trouvées :</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('staff.update', $staff->staff_id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Column 1: Identity & Account -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Identity Card -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Identité & Compte
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                            <input type="text" name="first_name" value="{{ old('first_name', $staff->first_name) }}" required autocomplete="given-name"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="last_name" value="{{ old('last_name', $staff->last_name) }}" required autocomplete="family-name"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">@</span>
                                </div>
                                <input type="text" name="username" value="{{ old('username', $staff->username) }}" required autocomplete="username"
                                       class="w-full pl-8 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rôle <span class="text-red-500">*</span></label>
                            <select name="role_id" required
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                                @foreach($roles as $r)
                                    <option value="{{ $r->role_id }}" {{ old('role_id', $staff->role_id) == $r->role_id ? 'selected' : '' }}>
                                        {{ ucfirst($r->role_name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                            <input type="password" name="password" placeholder="Laisser vide pour ne pas changer" autocomplete="new-password"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer mot de passe</label>
                            <input type="password" name="password_confirmation"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                    </div>
                </div>

                <!-- Contact & Professional Info -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Informations Professionnelles
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="{{ old('email', $staff->email) }}" autocomplete="email"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input type="tel" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" autocomplete="tel"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Spécialité</label>
                            <input type="text" name="specialty" value="{{ old('specialty', $staff->specialty) }}" placeholder="Ex: Natation, Yoga..."
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date d'embauche</label>
                            <input type="date" name="hiring_date" value="{{ old('hiring_date', $staff->hiring_date) }}"
                                   class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Settings & Compensation -->
            <div class="space-y-6">
                <!-- Status & Badge -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 19.464a3 3 0 01-.879.879l-2.121 2.121h-2.828l-3.172-3.172v-2.828l2.121-2.121a3 3 0 01.879-.879l4.728-4.728A6 6 0 1115 7z"/>
                            </svg>
                            Accès & Statut
                        </h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Compte Actif</label>
                                <p class="text-xs text-gray-500">Autoriser la connexion</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" {{ $staff->is_active ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge UID</label>
                            <div class="flex gap-2">
                                <input type="text" name="badge_uid" value="{{ old('badge_uid', $staff->badges->first()->badge_uid ?? '') }}" placeholder="Scanner..."
                                       class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                                <button type="button" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 border border-gray-300" title="Scanner le badge (Fonctionnalité à venir)">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compensation -->
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Rémunération
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type de salaire</label>
                            <select name="salary_type" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                                <option value="">-- Sélectionner --</option>
                                <option value="hourly" {{ old('salary_type', $staff->salary_type) == 'hourly' ? 'selected' : '' }}>Horaire</option>
                                <option value="monthly" {{ old('salary_type', $staff->salary_type) == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                                <option value="per_session" {{ old('salary_type', $staff->salary_type) == 'per_session' ? 'selected' : '' }}>Par Séance</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Taux / Salaire</label>
                            <div class="relative">
                                <input type="number" step="0.01" name="hourly_rate" value="{{ old('hourly_rate', $staff->hourly_rate) }}"
                                       class="w-full pl-3 pr-12 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">DZD</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Width: Notes -->
            <div class="lg:col-span-3">
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Notes & Remarques</h3>
                    </div>
                    <div class="p-6">
                        <textarea name="notes" rows="3" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 shadow-sm transition" placeholder="Informations complémentaires...">{{ old('notes', $staff->notes) }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
            <a href="{{ route('staff.index') }}" class="px-6 py-2.5 bg-white border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition shadow-sm">
                Annuler
            </a>
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
