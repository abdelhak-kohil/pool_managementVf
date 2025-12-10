@extends('layouts.app')
@section('title', 'Ajouter une Catégorie')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6">Ajouter une Catégorie</h2>

    <form action="{{ route('categories.store') }}" method="POST" class="space-y-4">
        @csrf
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la catégorie</label>
            <input type="text" name="name" required class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select name="type" required class="w-full bg-gray-50 border border-gray-300 rounded-lg p-2.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="product">Produit (Vente)</option>
                <option value="equipment">Équipement (Inventaire)</option>
            </select>
        </div>

        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('categories.index') }}" class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition">Annuler</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
