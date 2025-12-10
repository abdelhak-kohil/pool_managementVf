@extends('layouts.app')
@section('title', 'Modifier l\'activité')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ color: '{{ old('color_code', $activity->color_code) }}' }">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Modifier l'activité</h1>
            <nav class="flex text-sm text-gray-500 mt-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-600 transition-colors">Accueil</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li><a href="{{ route('activities.index') }}" class="hover:text-blue-600 transition-colors">Activités</a></li>
                    <li><span class="text-gray-400">/</span></li>
                    <li class="text-gray-900 font-medium">Modifier : {{ $activity->name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('activities.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Retour
            </a>
        </div>
    </div>

    <!-- Form Container -->
    <div class="max-w-3xl mx-auto">
        <form action="{{ route('activities.update', $activity->activity_id) }}" method="POST" class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden relative">
            @csrf
            @method('PUT')
            
             <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-lg font-semibold text-gray-900">Détails de l'activité</h2>
                <p class="text-sm text-gray-500 mt-1">Modifiez les informations ci-dessous.</p>
            </div>

            <div class="p-8 space-y-8">
                <!-- Name Field -->
                <div>
                     <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Nom de l'activité <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <input type="text" name="name" id="name" value="{{ old('name', $activity->name) }}" required
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-lg py-2.5 transition ease-in-out duration-150"
                               placeholder="Ex: Aquabike Dynamique">
                    </div>
                    @error('name') <p class="mt-1 text-sm text-red-600 flex items-center gap-1"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                </div>

                <!-- Access Type Field -->
                <div>
                    <label for="access_type" class="block text-sm font-semibold text-gray-700 mb-2">Code d'accès <span class="text-red-500">*</span></label>
                    <div class="relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                        </div>
                        <input type="text" name="access_type" id="access_type" value="{{ old('access_type', $activity->access_type) }}" required
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-lg py-2.5 transition ease-in-out duration-150"
                               placeholder="Ex: aquagym">
                    </div>
                    <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                         <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Valeurs standards : men, women, aquagym, group
                    </p>
                    @error('access_type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                     <!-- Color Picker -->
                    <div x-data>
                         <label class="block text-sm font-semibold text-gray-700 mb-2">Couleur de l'activité <span class="text-red-500">*</span></label>
                        <div class="flex items-center gap-4 p-3 border border-gray-200 rounded-lg bg-gray-50/50">
                            <div class="relative h-10 w-10 flex-shrink-0">
                                <input type="color" name="color_code" x-model="color"
                                       class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10">
                                <div class="h-10 w-10 rounded-full shadow-sm border border-gray-200" :style="`background-color: ${color}`"></div>
                            </div>
                            <div class="flex-1">
                                 <input type="text" x-model="color" name="color_code_text"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono uppercase tracking-wider py-2"
                                   maxlength="7">
                            </div>
                        </div>
                        @error('color_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <!-- Active Toggle -->
                    <div>
                         <label class="block text-sm font-semibold text-gray-700 mb-3">Statut opérationnel</label>
                         
                         <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ old('is_active', $activity->is_active) ? 'checked' : '' }}>
                             <!-- Track -->
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600 shadow-inner"></div>
                            
                            <span class="ml-3 text-sm font-medium text-gray-700 peer-checked:text-blue-700 transition-colors">
                                Actif et visible
                            </span>
                        </label>
                         <p class="mt-2 text-xs text-gray-500">Désactivez pour masquer l'activité dans le planning.</p>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row sm:justify-end gap-3 items-center">
                <a href="{{ route('activities.index') }}" class="w-full sm:w-auto px-5 py-2.5 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all text-center">
                    Annuler
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 border border-transparent rounded-lg shadow-md text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:-translate-y-0.5 transition-all text-center">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
