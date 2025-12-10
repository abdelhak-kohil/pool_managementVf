@extends('layouts.app')
@section('title', 'Gestion des Catégories')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">📂 Catégories</h2>
        <a href="{{ route('categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
            <span>+ Ajouter</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="p-4 rounded-tl-lg">Nom</th>
                    <th class="p-4">Type</th>
                    <th class="p-4 rounded-tr-lg text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($categories as $category)
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-4 font-medium text-gray-800">{{ $category->name }}</td>
                    <td class="p-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $category->type === 'product' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700' }}">
                            {{ $category->type === 'product' ? 'Produit' : 'Équipement' }}
                        </span>
                    </td>
                    <td class="p-4 text-right flex justify-end gap-2">
                        <a href="{{ route('categories.edit', $category) }}" class="text-blue-600 hover:bg-blue-50 p-2 rounded-lg transition">
                            ✏️
                        </a>
                        <form action="{{ route('categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:bg-red-50 p-2 rounded-lg transition">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $categories->links() }}
    </div>
</div>
@endsection
