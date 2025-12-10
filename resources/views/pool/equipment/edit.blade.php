@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.equipment.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux équipements</a>
        <h1 class="text-3xl font-bold text-slate-800">Modifier {{ $equipment->name }}</h1>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('pool.equipment.update', $equipment) }}" method="POST" class="bg-white rounded-xl shadow-sm p-8">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nom de l'équipement</label>
                    <input type="text" name="name" value="{{ old('name', $equipment->name) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                    <select name="type" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="pump" {{ $equipment->type == 'pump' ? 'selected' : '' }}>Pompe</option>
                        <option value="filter" {{ $equipment->type == 'filter' ? 'selected' : '' }}>Filtre</option>
                        <option value="heater" {{ $equipment->type == 'heater' ? 'selected' : '' }}>Chauffage</option>
                        <option value="chemical_doser" {{ $equipment->type == 'chemical_doser' ? 'selected' : '' }}>Doseur Automatique</option>
                        <option value="cleaning_robot" {{ $equipment->type == 'cleaning_robot' ? 'selected' : '' }}>Robot Nettoyeur</option>
                        <option value="other" {{ $equipment->type == 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                    @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Serial Number -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Numéro de Série</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number', $equipment->serial_number) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    @error('serial_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Emplacement</label>
                    <input type="text" name="location" value="{{ old('location', $equipment->location) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    @error('location') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Install Date -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Date d'Installation</label>
                        <input type="date" name="install_date" value="{{ $equipment->install_date ? $equipment->install_date->format('Y-m-d') : '' }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @error('install_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Next Due Date -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Prochaine Maintenance</label>
                        <input type="date" name="next_due_date" value="{{ $equipment->next_due_date ? $equipment->next_due_date->format('Y-m-d') : '' }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @error('next_due_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Statut</label>
                    <select name="status" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="operational" {{ $equipment->status == 'operational' ? 'selected' : '' }}>Opérationnel</option>
                        <option value="warning" {{ $equipment->status == 'warning' ? 'selected' : '' }}>Avertissement</option>
                        <option value="failure" {{ $equipment->status == 'failure' ? 'selected' : '' }}>En Panne</option>
                    </select>
                    @error('status') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">{{ old('notes', $equipment->notes) }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-8 flex justify-between items-center">
                <button type="button" onclick="confirmDelete()" class="text-red-600 hover:text-red-800 text-sm font-medium">Supprimer l'équipement</button>
                <div class="flex gap-4">
                    <a href="{{ route('pool.equipment.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                    <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Enregistrer</button>
                </div>
            </div>
        </form>

        <form id="delete-form" action="{{ route('pool.equipment.destroy', $equipment) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet équipement ? Cette action est irréversible.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endsection
