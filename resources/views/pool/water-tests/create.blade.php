@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.water-tests.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux relevés</a>
        <h1 class="text-3xl font-bold text-slate-800">Nouveau Relevé</h1>
        <p class="text-slate-500">Saisir les paramètres de qualité de l'eau</p>
    </div>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('pool.water-tests.store') }}" method="POST" class="bg-white rounded-xl shadow-sm p-8">
            @csrf

            <!-- Pool & Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Bassin</label>
                    <select name="pool_id" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @foreach($pools as $pool)
                            <option value="{{ $pool->facility_id }}">{{ $pool->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Date et Heure</label>
                    <input type="datetime-local" name="test_date" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="border-t border-slate-100 pt-8 mb-8">
                <h3 class="text-lg font-semibold text-slate-800 mb-6">Paramètres Physico-chimiques</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- pH -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">pH</label>
                        <div class="relative">
                            <input type="number" step="0.1" name="ph" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500 pr-8" placeholder="7.2">
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Cible: 7.2 - 7.6</p>
                    </div>

                    <!-- Chlorine Free -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Chlore Libre (ppm)</label>
                        <input type="number" step="0.01" name="chlorine_free" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="1.5">
                        <p class="text-xs text-slate-400 mt-1">Cible: 1.0 - 2.0</p>
                    </div>

                    <!-- Chlorine Total -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Chlore Total (ppm)</label>
                        <input type="number" step="0.01" name="chlorine_total" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Temperature -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Température (°C)</label>
                        <input type="number" step="0.1" name="temperature" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="28.0">
                    </div>

                    <!-- Alkalinity (TAC) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">TAC (mg/L)</label>
                        <input type="number" step="1" name="alkalinity" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Hardness (TH) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">TH (mg/L)</label>
                        <input type="number" step="1" name="hardness" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-8 mb-8">
                <h3 class="text-lg font-semibold text-slate-800 mb-6">Observations</h3>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Commentaires</label>
                    <textarea name="comments" rows="3" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Eau trouble, odeur particulière, etc."></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('pool.water-tests.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Enregistrer le relevé</button>
            </div>
        </form>
    </div>
</div>
@endsection
