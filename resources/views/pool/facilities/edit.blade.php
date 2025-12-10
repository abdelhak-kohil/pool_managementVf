@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.facilities.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux bassins</a>
        <h1 class="text-3xl font-bold text-slate-800">Modifier {{ $facility->name }}</h1>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('pool.facilities.update', $facility) }}" method="POST" class="bg-white rounded-xl shadow-sm p-8">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nom du Bassin</label>
                    <input type="text" name="name" value="{{ old('name', $facility->name) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type</label>
                    <select name="type" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="indoor_pool" {{ $facility->type == 'indoor_pool' ? 'selected' : '' }}>Piscine Intérieure</option>
                        <option value="outdoor_pool" {{ $facility->type == 'outdoor_pool' ? 'selected' : '' }}>Piscine Extérieure</option>
                        <option value="spa" {{ $facility->type == 'spa' ? 'selected' : '' }}>Spa / Jacuzzi</option>
                        <option value="wading_pool" {{ $facility->type == 'wading_pool' ? 'selected' : '' }}>Pataugeoire</option>
                    </select>
                </div>

                <!-- Volume -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Volume (Litres)</label>
                    <input type="number" name="volume_liters" value="{{ old('volume_liters', $facility->volume_liters) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" required>
                </div>

                <!-- Temperature Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Temp. Min (°C)</label>
                        <input type="number" step="0.1" name="min_temperature" value="{{ old('min_temperature', $facility->min_temperature) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Temp. Max (°C)</label>
                        <input type="number" step="0.1" name="max_temperature" value="{{ old('max_temperature', $facility->max_temperature) }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Statut</label>
                    <select name="status" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="operational" {{ $facility->status == 'operational' ? 'selected' : '' }}>Opérationnel</option>
                        <option value="closed" {{ $facility->status == 'closed' ? 'selected' : '' }}>Fermé</option>
                        <option value="under_maintenance" {{ $facility->status == 'under_maintenance' ? 'selected' : '' }}>En Maintenance</option>
                    </select>
                </div>

                <!-- Active -->
                <div class="flex items-center gap-2">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" {{ $facility->active ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <label class="text-sm text-slate-700">Actif</label>
                </div>
            </div>

            <div class="mt-8 flex justify-between items-center">
                <button type="button" onclick="confirmDelete()" class="text-red-600 hover:text-red-800 text-sm font-medium">Supprimer le bassin</button>
                <div class="flex gap-4">
                    <a href="{{ route('pool.facilities.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                    <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Enregistrer</button>
                </div>
            </div>
        </form>

        <form id="delete-form" action="{{ route('pool.facilities.destroy', $facility) }}" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>

<script>
function confirmDelete() {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce bassin ? Cette action est irréversible.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>
@endsection
