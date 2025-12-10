@extends('layouts.app')
@section('title', 'Paramètres RH')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="md:flex md:items-center md:justify-between mb-8">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Paramètres de Présence
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Configuration des seuils pour le calcul automatique des heures.
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4">
                <a href="{{ route('staff.hr.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Retour au tableau de bord
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <form action="{{ route('staff.hr.settings.update') }}" method="POST">
                @csrf
                <div class="px-4 py-5 sm:p-6 space-y-6">
                    
                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                        
                        <!-- Heures de nuit -->
                        <div class="sm:col-span-3">
                            <label for="night_start" class="block text-sm font-medium text-gray-700">Début Heures de Nuit</label>
                            <div class="mt-1">
                                <input type="time" name="night_start" id="night_start" value="{{ old('night_start', $settings['night_start']) }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Heure à partir de laquelle la présence est comptée comme heure de nuit.</p>
                            @error('night_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-3">
                            <label for="night_end" class="block text-sm font-medium text-gray-700">Fin Heures de Nuit</label>
                            <div class="mt-1">
                                <input type="time" name="night_end" id="night_end" value="{{ old('night_end', $settings['night_end']) }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Heure de fin de la comptabilisation des heures de nuit.</p>
                            @error('night_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Heures Supplémentaires -->
                        <div class="sm:col-span-6">
                            <label for="overtime_threshold" class="block text-sm font-medium text-gray-700">Seuil Heures Supplémentaires (Heures)</label>
                            <div class="mt-1">
                                <input type="number" step="0.5" name="overtime_threshold" id="overtime_threshold" value="{{ old('overtime_threshold', $settings['overtime_threshold']) }}" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Nombre d'heures de travail par jour au-delà duquel les heures sont comptées comme supplémentaires (ex: 8).</p>
                             @error('overtime_threshold') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 text-right sm:px-6">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
