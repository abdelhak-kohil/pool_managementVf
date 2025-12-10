@extends('layouts.app')
@section('title', 'Modifier Groupe Partenaire')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-gray-800">🏫 Modifier Groupe Partenaire</h2>
        <a href="{{ route('partner-groups.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
            ← Retour
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white shadow rounded-xl border border-gray-100 p-8">
        <form action="{{ route('partner-groups.update', $group->group_id) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-gray-700 font-medium mb-2">Nom du groupe</label>
                <input type="text" name="name" value="{{ old('name', $group->name) }}" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                       required>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Nom du contact</label>
                <input type="text" name="contact_name" value="{{ old('contact_name', $group->contact_name) }}" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Téléphone</label>
                <input type="text" name="contact_phone" value="{{ old('contact_phone', $group->contact_phone) }}" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email', $group->email) }}" 
                       class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
            </div>

            <div class="md:col-span-2">
                <label class="block text-gray-700 font-medium mb-2">Notes</label>
                <textarea name="notes" rows="3" 
                          class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">{{ old('notes', $group->notes) }}</textarea>
            </div>

            <div class="md:col-span-2 flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection