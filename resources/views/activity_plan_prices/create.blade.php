@extends('layouts.app')
@section('title', 'Nouvelle Tarification')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-gray-800">➕ Ajouter une tarification</h2>
        <a href="{{ route('activity-plan-prices.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
            ← Retour
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white shadow rounded-xl border border-gray-100 p-8">
        <form action="{{ route('activity-plan-prices.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf

            <div>
                <label class="block text-gray-700 font-medium mb-2">Activité</label>
                <select name="activity_id" 
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                        required>
                    <option value="">— Sélectionner —</option>
                    @foreach($activities as $a)
                        <option value="{{ $a->activity_id }}">{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Plan</label>
                <select name="plan_id" 
                        class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                        required>
                    <option value="">— Sélectionner —</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->plan_id }}">{{ $p->plan_name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Prix (DZD)</label>
                <input type="number" step="0.01" name="price" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                       required>
            </div>

            <div class="md:col-span-2 flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
