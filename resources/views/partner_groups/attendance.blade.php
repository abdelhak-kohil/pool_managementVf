@extends('layouts.app')
@section('title', 'Historique - ' . $partnerGroup->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('partner-groups.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">Groupes</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <a href="{{ route('partner-groups.edit', $partnerGroup->group_id) }}" class="ml-1 text-gray-500 hover:text-gray-700 font-medium text-sm md:ml-2">{{ $partnerGroup->name }}</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-gray-400 font-medium text-sm md:ml-2">Historique</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">Historique des Passages</h2>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('partner-groups.edit', $partnerGroup->group_id) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Retour
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-start space-x-4 hover:shadow-md transition-shadow">
            <div class="flex-shrink-0 p-3 bg-blue-50 rounded-xl text-blue-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Passages (Ce mois)</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">
                    {{ $partnerGroup->attendances()->whereMonth('access_time', now()->month)->count() }}
                </p>
                <p class="mt-1 text-xs text-green-600 font-medium flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1"></span>
                    Enregistrés
                </p>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-start space-x-4 hover:shadow-md transition-shadow">
            <div class="flex-shrink-0 p-3 bg-purple-50 rounded-xl text-purple-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Visiteurs (Ce mois)</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">
                    {{ $partnerGroup->attendances()->whereMonth('access_time', now()->month)->sum('attendee_count') }}
                </p>
                <p class="mt-1 text-xs text-purple-600 font-medium flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500 mr-1"></span>
                    Personnes
                </p>
            </div>
        </div>
        
        <!-- Card 3 (Refus) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-start space-x-4 hover:shadow-md transition-shadow">
            <div class="flex-shrink-0 p-3 bg-red-50 rounded-xl text-red-600">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                 <p class="text-sm font-medium text-gray-500">Refus (Ce mois)</p>
                 <p class="mt-1 text-3xl font-bold text-gray-900">
                     {{ $partnerGroup->attendances()->whereMonth('access_time', now()->month)->where('access_decision', 'denied')->count() }}
                 </p>
                 <div class="mt-1 text-xs text-red-500 font-medium">Attention requise</div>
            </div>
        </div>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Toolbar -->
        <div class="p-5 border-b border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h3 class="text-lg font-semibold text-gray-900">Journal des accès</h3>
            
            <form method="GET" class="flex items-center gap-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <input type="date" name="date" value="{{ request('date') }}" 
                           class="pl-9 block w-full rounded-lg border-gray-300 sm:text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                           onchange="this.form.submit()">
                </div>
                
                @if(request('date'))
                    <a href="{{ route('partner-groups.attendance', $partnerGroup->group_id) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                        <svg class="h-4 w-4 mr-1 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Effacer
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date & Heure</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Badge</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Détails Créneau</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Personnes</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Staff</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $log->access_time->format('H:i') }}</div>
                            <div class="text-xs text-gray-500">{{ $log->access_time->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 font-mono">
                                {{ optional($log->badge)->badge_uid ?? 'MANUEL' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->slot)
                                <div class="text-sm text-gray-900">
                                    {{ \Carbon\Carbon::parse($log->slot->slot->start_time)->format('H:i') }} - 
                                    {{ \Carbon\Carbon::parse($log->slot->slot->end_time)->format('H:i') }}
                                </div>
                                @if($log->slot->slot->activity)
                                    <div class="text-xs text-gray-500">{{ $log->slot->slot->activity->name }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-0.5">Max: {{ $log->slot->max_capacity }}</div>
                            @else
                                <span class="text-sm text-gray-400 italic">Hors créneau</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm font-bold text-gray-900 mr-2">{{ $log->attendee_count }}</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ optional($log->staff)->first_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->access_decision === 'granted')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-2 h-2 mr-1.5 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                    Autorisé
                                </span>
                            @else
                                <div class="flex flex-col items-start">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-2 h-2 mr-1.5 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        Refusé
                                    </span>
                                    @if($log->denial_reason)
                                        <span class="text-[10px] text-red-500 mt-1 max-w-[150px] truncate" title="{{ $log->denial_reason }}">
                                            {{ $log->denial_reason }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900">Aucun historique trouvé</h3>
                                <p class="text-sm text-gray-500 mt-1">Aucun passage n'a été enregistré pour cette période.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
