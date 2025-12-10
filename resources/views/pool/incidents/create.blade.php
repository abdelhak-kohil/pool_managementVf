@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.incidents.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux incidents</a>
        <h1 class="text-3xl font-bold text-slate-800">Signaler un Incident</h1>
        <p class="text-slate-500">Rapporter une panne, une fuite ou une anomalie</p>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('pool.incidents.store') }}" method="POST" class="bg-white rounded-xl shadow-sm p-8 border-t-4 border-red-500">
            @csrf

            <div class="space-y-6">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Titre de l'incident</label>
                    <input type="text" name="title" class="w-full rounded-lg border-slate-200 focus:border-red-500 focus:ring-red-500" placeholder="Ex: Pompe bruyante, Fuite filtre..." required>
                </div>

                <!-- Severity -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sévérité</label>
                    <div class="grid grid-cols-4 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="severity" value="low" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border border-slate-200 peer-checked:bg-blue-50 peer-checked:border-blue-500 peer-checked:text-blue-700 hover:bg-slate-50 transition">
                                <span class="block text-sm font-medium">Faible</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="severity" value="medium" class="peer sr-only" checked>
                            <div class="text-center p-3 rounded-lg border border-slate-200 peer-checked:bg-yellow-50 peer-checked:border-yellow-500 peer-checked:text-yellow-700 hover:bg-slate-50 transition">
                                <span class="block text-sm font-medium">Moyenne</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="severity" value="high" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border border-slate-200 peer-checked:bg-orange-50 peer-checked:border-orange-500 peer-checked:text-orange-700 hover:bg-slate-50 transition">
                                <span class="block text-sm font-medium">Haute</span>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="severity" value="critical" class="peer sr-only">
                            <div class="text-center p-3 rounded-lg border border-slate-200 peer-checked:bg-red-50 peer-checked:border-red-500 peer-checked:text-red-700 hover:bg-slate-50 transition">
                                <span class="block text-sm font-medium">Critique</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Context (Pool or Equipment) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Bassin Concerné (Optionnel)</label>
                        <select name="pool_id" class="w-full rounded-lg border-slate-200 focus:border-red-500 focus:ring-red-500">
                            <option value="">-- Aucun --</option>
                            @foreach($pools as $pool)
                                <option value="{{ $pool->facility_id }}">{{ $pool->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Équipement Concerné (Optionnel)</label>
                        <select name="equipment_id" class="w-full rounded-lg border-slate-200 focus:border-red-500 focus:ring-red-500">
                            <option value="">-- Aucun --</option>
                            @foreach($equipment as $item)
                                <option value="{{ $item->equipment_id }}" {{ request('equipment_id') == $item->equipment_id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description détaillée</label>
                    <textarea name="description" rows="5" class="w-full rounded-lg border-slate-200 focus:border-red-500 focus:ring-red-500" placeholder="Décrivez le problème, les symptômes observés, etc." required></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="{{ route('pool.incidents.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition shadow-sm">Signaler l'incident</button>
            </div>
        </form>
    </div>
</div>
@endsection
