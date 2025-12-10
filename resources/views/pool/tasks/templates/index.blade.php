@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Modèles de Tâches</h1>
            <p class="text-slate-500">Gérer les modèles de check-lists personnalisés</p>
        </div>
        <a href="{{ route('pool.tasks.templates.create') }}" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouveau Modèle
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full text-left text-sm text-slate-600">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-6 py-4 font-semibold">Nom</th>
                    <th class="px-6 py-4 font-semibold">Type</th>
                    <th class="px-6 py-4 font-semibold">Statut</th>
                    <th class="px-6 py-4 font-semibold">Champs</th>
                    <th class="px-6 py-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($templates as $template)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4 font-medium text-slate-900">
                            {{ $template->name }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium 
                                {{ $template->type === 'daily' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $template->type === 'weekly' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $template->type === 'monthly' ? 'bg-orange-100 text-orange-800' : '' }}">
                                {{ ucfirst($template->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($template->is_active)
                                <span class="text-green-600 font-medium flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Actif
                                </span>
                            @else
                                <span class="text-slate-400">Inactif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            {{ count($template->items ?? []) }} champs
                        </td>
                        <td class="px-6 py-4 text-right flex justify-end gap-2">
                            <a href="{{ route('pool.tasks.templates.edit', $template) }}" class="text-blue-600 hover:text-blue-900 font-medium">Modifier</a>
                            <form action="{{ route('pool.tasks.templates.destroy', $template) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                            Aucun modèle trouvé.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
