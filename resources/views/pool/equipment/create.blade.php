@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.equipment.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux équipements</a>
        <h1 class="text-3xl font-bold text-slate-800">Nouvel Équipement</h1>
        <p class="text-slate-500">Ajouter un nouvel équipement au parc</p>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('pool.equipment.store') }}" method="POST" class="bg-white rounded-xl shadow-sm p-8">
            @csrf

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nom de l'équipement</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Pompe Principale Bassin A" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                    <select name="type" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="pump">Pompe</option>
                        <option value="filter">Filtre</option>
                        <option value="heater">Chauffage</option>
                        <option value="chemical_doser">Doseur Automatique</option>
                        <option value="cleaning_robot">Robot Nettoyeur</option>
                        <option value="other">Autre</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Numéro de Série</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: SN-123456789">
                    @error('serial_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Emplacement</label>
                    <input type="text" name="location" value="{{ old('location') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: Local Technique 1">
                    @error('location') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Install Date -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Date d'Installation</label>
                        <input type="date" name="install_date" value="{{ old('install_date') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @error('install_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Next Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Prochaine Maintenance</label>
                        <input type="date" name="next_due_date" value="{{ old('next_due_date') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @error('next_due_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Statut Initial</label>
                    <select name="status" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="operational">Opérationnel</option>
                        <option value="warning">Avertissement</option>
                        <option value="failure">En Panne</option>
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="{{ route('pool.equipment.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Créer l'équipement</button>
            </div>
        </form>
    </div>
</div>
@endsection
