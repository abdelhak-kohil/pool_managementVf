@extends('layouts.app')
@section('title', 'Nouveau Produit')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h2 class="text-2xl font-semibold text-gray-800">➕ Ajouter un Produit</h2>
  <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-800 flex items-center gap-2">
    <span>←</span> Retour
  </a>
</div>

<div class="bg-white rounded-xl shadow border border-gray-100 p-6 max-w-4xl mx-auto">
  <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Name -->
        <div class="col-span-2">
            <label class="block text-gray-700 font-medium mb-2">Nom du produit <span class="text-red-500">*</span></label>
            <input type="text" name="name" required
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>

        <!-- Category -->
        <div>
            <label class="block text-gray-700 font-medium mb-2">Catégorie <span class="text-red-500">*</span></label>
            <select name="category_id" required
                    class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                <option value="">Sélectionner une catégorie</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Stock -->
        <div>
            <label class="block text-gray-700 font-medium mb-2">Stock Initial <span class="text-red-500">*</span></label>
            <input type="number" name="stock_quantity" required min="0"
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>

        <!-- Purchase Price -->
        <div>
            <label class="block text-gray-700 font-medium mb-2">Prix d'achat (DZD) <span class="text-red-500">*</span></label>
            <input type="number" name="purchase_price" required min="0" step="0.01"
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>

        <!-- Selling Price -->
        <div>
            <label class="block text-gray-700 font-medium mb-2">Prix de vente (DZD) <span class="text-red-500">*</span></label>
            <input type="number" name="price" required min="0" step="0.01"
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>

        <!-- Alert Threshold -->
        <div>
            <label class="block text-gray-700 font-medium mb-2">Seuil d'alerte stock</label>
            <input type="number" name="alert_threshold" value="5" min="0"
                   class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5">
        </div>
    </div>

    <!-- Description -->
    <div>
        <label class="block text-gray-700 font-medium mb-2">Description</label>
        <textarea name="description" rows="3"
                  class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5"></textarea>
    </div>

    <!-- Images -->
    <div>
        <label class="block text-gray-700 font-medium mb-2">Images du produit</label>
        <div class="flex items-center justify-center w-full">
            <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                    </svg>
                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Cliquez pour uploader</span> ou glissez-déposez</p>
                    <p class="text-xs text-gray-500">PNG, JPG or JPEG (MAX. 2MB)</p>
                </div>
                <input id="dropzone-file" type="file" name="images[]" multiple class="hidden" />
            </label>
        </div>
    </div>

    <div class="flex justify-end pt-4">
        <button type="submit" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-lg flex items-center gap-2">
            <span>💾 Enregistrer le produit</span>
        </button>
    </div>
  </form>
</div>
@endsection
