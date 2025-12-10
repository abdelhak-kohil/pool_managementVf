@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Incidents</h1>
            <p class="text-slate-500">Suivi des pannes et anomalies</p>
        </div>
        <a href="{{ route('pool.incidents.create') }}" class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white shadow hover:bg-red-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Signaler Incident
        </a>
    </div>

    <div class="grid grid-cols-1 gap-4">
        @forelse($incidents as $incident)
            <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 {{ $incident->severity == 'critical' ? 'border-red-600' : ($incident->severity == 'high' ? 'border-orange-500' : ($incident->severity == 'medium' ? 'border-yellow-500' : 'border-blue-400')) }} hover:shadow-md transition">
                <div class="flex flex-col md:flex-row justify-between md:items-center gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $incident->status == 'open' ? 'bg-red-100 text-red-700' : ($incident->status == 'resolved' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700') }}">
                                {{ ucfirst(str_replace('_', ' ', $incident->status)) }}
                            </span>
                            <span class="text-xs text-slate-400">{{ $incident->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $incident->title }}</h3>
                        <p class="text-slate-600 text-sm mb-2 line-clamp-2">{{ $incident->description }}</p>
                        <div class="flex items-center gap-4 text-xs text-slate-500">
                            @if($incident->pool)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                    {{ $incident->pool->name }}
                                </span>
                            @endif
                            @if($incident->equipment)
                                <span class="flex items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ $incident->equipment->name }}
                                </span>
                            @endif
                            <span>Par: {{ $incident->creator->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden md:block">
                            <p class="text-xs text-slate-400 uppercase">Assigné à</p>
                            <p class="font-medium text-slate-700">{{ $incident->assignee->name ?? 'Non assigné' }}</p>
                        </div>
                        <a href="{{ route('pool.incidents.show', $incident) }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-medium">
                            Gérer
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12 bg-white rounded-xl border border-dashed border-slate-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-slate-900">Aucun incident en cours</h3>
                <p class="text-slate-500">Tout semble fonctionner correctement.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
