@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.incidents.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux incidents</a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">{{ $incident->title }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $incident->severity == 'critical' ? 'bg-red-100 text-red-700' : ($incident->severity == 'high' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ ucfirst($incident->severity) }}
                    </span>
                    <span class="text-slate-500 text-sm">Signalé le {{ $incident->created_at->format('d/m/Y à H:i') }} par {{ $incident->creator->name ?? 'Inconnu' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
            <!-- Description Card -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Description</h3>
                <p class="text-slate-600 whitespace-pre-line">{{ $incident->description }}</p>
                
                @if($incident->pool || $incident->equipment)
                    <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                        @if($incident->pool)
                            <div>
                                <p class="text-xs text-slate-400 uppercase">Bassin</p>
                                <p class="font-medium text-slate-700">{{ $incident->pool->name }}</p>
                            </div>
                        @endif
                        @if($incident->equipment)
                            <div>
                                <p class="text-xs text-slate-400 uppercase">Équipement</p>
                                <p class="font-medium text-slate-700">{{ $incident->equipment->name }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Resolution Notes (Placeholder for future comments/updates) -->
            <div class="bg-white rounded-xl shadow-sm p-6 opacity-50">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Journal d'intervention</h3>
                <p class="text-slate-400 italic">Fonctionnalité de commentaires à venir...</p>
            </div>
        </div>

        <!-- Actions Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Gestion</h3>
                <form action="{{ route('pool.incidents.update', $incident) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                        <select name="status" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="open" {{ $incident->status == 'open' ? 'selected' : '' }}>Ouvert</option>
                            <option value="assigned" {{ $incident->status == 'assigned' ? 'selected' : '' }}>Assigné</option>
                            <option value="in_progress" {{ $incident->status == 'in_progress' ? 'selected' : '' }}>En Cours</option>
                            <option value="waiting_parts" {{ $incident->status == 'waiting_parts' ? 'selected' : '' }}>Attente Pièces</option>
                            <option value="resolved" {{ $incident->status == 'resolved' ? 'selected' : '' }}>Résolu</option>
                            <option value="closed" {{ $incident->status == 'closed' ? 'selected' : '' }}>Fermé</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Assigner à</label>
                        <select name="assigned_to" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Personne --</option>
                            @foreach($technicians as $tech)
                                <option value="{{ $tech->staff_id }}" {{ $incident->assigned_to == $tech->staff_id ? 'selected' : '' }}>
                                    {{ $tech->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        Mettre à jour
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
