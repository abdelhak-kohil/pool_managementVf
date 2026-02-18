@extends('layouts.app')
@section('title', 'Nouveau Groupe Partenaire')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('partner-groups.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">Groupes</a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-gray-400 font-medium text-sm md:ml-2">Nouveau</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Nouveau Groupe Partenaire</h2>
            <p class="text-gray-500 mt-1">Créez un nouvel accès groupe pour les associations, écoles ou entreprises.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('partner-groups.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Annuler
            </a>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-6 md:p-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center">
                <span class="bg-blue-100 text-blue-600 p-2 rounded-lg mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
                Informations du Groupe
            </h3>

            <form action="{{ route('partner-groups.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'organisation <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" 
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                               placeholder="Ex: Association Sportive des Nageurs"
                               required>
                    </div>

                    <!-- Contact Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom du contact référent</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <input type="text" name="contact_name" value="{{ old('contact_name') }}" 
                                   class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                                   placeholder="Jean Dupont">
                        </div>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <div class="relative rounded-md shadow-sm">
                             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            </div>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" 
                                   class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                                   placeholder="06 12 34 56 78">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email de contact</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <input type="email" name="email" value="{{ old('email') }}" 
                                   class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                                   placeholder="contact@organisation.com">
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes internes</label>
                        <textarea name="notes" rows="4" 
                                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                                  placeholder="Observations, conditions particulières...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Partner Specifics -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-100 mt-4">
                    <!-- Partner Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de Partenaire <span class="text-red-500">*</span></label>
                        <select name="partner_type" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" required>
                            <option value="">-- Sélectionner --</option>
                            <option value="school" {{ old('partner_type') == 'school' ? 'selected' : '' }}>École / Établissement Scolare</option>
                            <option value="club" {{ old('partner_type') == 'club' ? 'selected' : '' }}>Club Sportif / Association</option>
                            <option value="enterprise" {{ old('partner_type') == 'enterprise' ? 'selected' : '' }}>Entreprise / CE</option>
                            <option value="other" {{ old('partner_type') == 'other' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>

                    <!-- Legal Entity -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dénomination Sociale (Juridique)</label>
                        <input type="text" name="legal_entity_name" value="{{ old('legal_entity_name') }}" 
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                               placeholder="Ex: SARL Sports Plus">
                    </div>

                    <!-- Reference Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Code de Référence Contrat</label>
                        <input type="text" name="contract_reference_code" value="{{ old('contract_reference_code') }}" 
                               class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5" 
                               placeholder="REF-2024-001">
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="mt-8 flex items-center justify-between gap-3 pt-6 border-t border-gray-100">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-blue-600">Étape 1/6</span> : Identification du groupe.
                        <br>La création du contrat et l'assignation des badges se feront à l'étape suivante.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('partner-groups.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Annuler
                        </a>
                        <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Suivant : Badges & Contrat
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
