@extends('layouts.app')
@section('title', 'Pointage Coachs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Historique de Pointage</h1>
            <p class="mt-2 text-sm text-gray-500">
                Consultez les entrées et sorties des coachs enregistrées par le système de badge.
            </p>
        </div>
        <a href="{{ route('coaches.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 rounded-xl shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition-all duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour Liste
        </a>
    </div>

    <!-- Stats & Filters Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Stats Cards -->
        <div class="lg:col-span-1 space-y-4">
            <!-- Total Logs -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 shadow-lg text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Pointages</p>
                        <p class="text-3xl font-bold mt-1">{{ $totalLogs }}</p>
                    </div>
                    <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-xs text-blue-100">
                     <span>Sur la période sélectionnée</span>
                </div>
            </div>

            <!-- Present Coaches -->
             <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Coachs Uniques</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $uniqueCoaches }}</p>
                    </div>
                    <div class="p-3 bg-purple-50 rounded-xl">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="lg:col-span-3 bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filtres de Recherche
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Date Range -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Période</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <span class="text-gray-400">à</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <!-- Coach Selector -->
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Coach</label>
                    <select name="coach_id" class="w-full rounded-xl border-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm bg-white">
                        <option value="">Tous les coachs</option>
                        @foreach($coaches as $coach)
                            <option value="{{ $coach->staff_id }}" {{ $coachId == $coach->staff_id ? 'selected' : '' }}>
                                {{ $coach->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Button -->
                <div class="flex items-end">
                    <button type="submit" class="w-full px-6 py-2.5 bg-gray-900 hover:bg-black text-white font-medium rounded-xl shadow transition flex items-center justify-center group">
                        <span class="mr-2">Actualiser</span>
                        <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Coach</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date & Heure</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Badge Utilisé</th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Statut</th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50/50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                        {{ substr($log->staff->first_name, 0, 1) }}{{ substr($log->staff->last_name, 0, 1) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $log->staff->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->staff->role->role_name ?? 'Staff' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 font-medium">{{ $log->access_time->format('H:i:s') }}</div>
                            <div class="text-xs text-gray-500">{{ $log->access_time->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-mono font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                <svg class="w-3 h-3 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                                {{ $log->badge_uid }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->access_decision === 'granted')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-2"></span>
                                    Autorisé
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-2"></span>
                                    Refusé
                                </span>
                            @endif
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            <!-- Placeholder for future actions if needed -->
                            <span class="text-gray-300">-</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                     <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                                <h3 class="text-sm font-medium text-gray-900">Aucun pointage trouvé</h3>
                                <p class="text-sm text-gray-500 mt-1">Essaie de modifier les filtres ou la période.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination (if applicable) -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
             <span class="text-xs text-gray-500">Affichage des 50 derniers enregistrements</span>
             <!-- Add pagination links here if method supports it -->
        </div>
    </div>

</div>
@endsection
