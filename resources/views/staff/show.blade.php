@extends('layouts.app')
@section('title', 'Profil Staff - ' . $staff->full_name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up" x-data="{ activeTab: 'overview' }">
    
    <!-- Header Card -->
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8 border border-gray-100">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 h-32 relative">
            <div class="absolute top-4 right-4 flex space-x-2">
                 <a href="{{ route('staff.edit', $staff->staff_id) }}" class="bg-white/20 backdrop-blur-md hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Modifier
                </a>
            </div>
        </div>
        <div class="px-8 pb-8">
            <div class="relative flex items-end -mt-12 mb-6">
                <div class="h-24 w-24 rounded-2xl bg-white p-1 shadow-lg">
                    <div class="h-full w-full bg-blue-50 rounded-xl flex items-center justify-center text-3xl font-bold text-blue-600 uppercase">
                        {{ substr($staff->first_name, 0, 1) }}{{ substr($staff->last_name, 0, 1) }}
                    </div>
                </div>
                <div class="ml-6 mb-1">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $staff->full_name }}</h1>
                    <div class="flex items-center gap-4 text-sm mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-medium bg-blue-100 text-blue-800">
                            {{ $staff->role->role_name ?? 'Staff' }}
                        </span>
                        <span class="text-gray-500 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            {{ $staff->email ?? 'Non renseigné' }}
                        </span>
                        <span class="text-gray-500 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            {{ $staff->phone_number ?? 'Non renseigné' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Badge UID</p>
                    <p class="text-lg font-mono font-bold text-slate-700">
                        {{ $staff->badges->first()->badge_uid ?? 'Aucun Badge' }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Taux de Retard (30j)</p>
                    <p class="text-lg font-bold {{ $latePercentage > 10 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $latePercentage }}%
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Dernier Accès</p>
                    <p class="text-lg font-bold text-slate-700">
                        {{ $staff->latestAccessLog ? $staff->latestAccessLog->access_time->format('d/m H:i') : '-' }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Statut</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-medium {{ $staff->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $staff->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" 
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Vue d'ensemble
                    </button>
                    <!-- 
                    <button @click="activeTab = 'planning'" 
                        :class="activeTab === 'planning' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" 
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Planning
                    </button>
                    -->
                    <button @click="activeTab = 'attendance'" 
                        :class="activeTab === 'attendance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" 
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Historique Présence
                    </button>
                     <button @click="activeTab = 'leaves'" 
                        :class="activeTab === 'leaves' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" 
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                        Congés & Absences
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <!-- Tab Contents -->
    <div class="space-y-6">
        
        <!-- OVERVIEW TAB -->
        <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Informations Personnelles</h3>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Nom Complet</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->full_name }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Nom d'utilisateur</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->username }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->email ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->phone_number ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Spécialité</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->specialty ?? '-' }}</dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500">Date d'embauche</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $staff->hiring_date ? \Carbon\Carbon::parse($staff->hiring_date)->format('d/m/Y') : '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes & Remarques</dt>
                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-md border border-gray-100">
                            {{ $staff->notes ?? 'Aucune note.' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- ATTENDANCE TAB -->
        <div x-show="activeTab === 'attendance'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Historique des 30 derniers jours</h3>
                    <a href="{{ route('staff.hr.dashboard') }}" class="text-sm text-blue-600 hover:text-blue-500 font-medium">Voir Dashboard RH &rarr;</a>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entrée</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sortie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Durée</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($attendances as $record)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $record->date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $record->check_in->format('H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $record->check_out ? $record->check_out->format('H:i') : '-' }}
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $record->working_hours > 0 ? $record->working_hours . ' h' : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($record->status)
                                    @case('present') <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Présent</span> @break
                                    @case('late') <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Retard ({{ $record->delay_minutes }}m)</span> @break
                                    @default <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($record->status) }}</span>
                                @endswitch
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">Aucun historique récent.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LEAVES TAB -->
        <div x-show="activeTab === 'leaves'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Demandes de Congés & Absences</h3>
                    <a href="{{ route('staff.leaves.index') }}" class="text-sm text-blue-600 hover:text-blue-500 font-medium">Gérer les congés &rarr;</a>
                </div>
                 <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Du</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Au</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Raison</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($leaves as $leave)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $typeColors = [
                                        'vacation' => 'bg-purple-100 text-purple-800',
                                        'sick' => 'bg-red-100 text-red-800',
                                        'absence' => 'bg-orange-100 text-orange-800',
                                    ];
                                    $typeLabels = [
                                        'vacation' => 'Congé',
                                        'sick' => 'Maladie',
                                        'absence' => 'Absence',
                                    ];
                                    $color = $typeColors[$leave->type] ?? 'bg-gray-100 text-gray-800';
                                    $label = $typeLabels[$leave->type] ?? $leave->type;
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $leave->start_date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $leave->end_date->format('d/m/Y') }}
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs">
                                {{ $leave->reason ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @switch($leave->status)
                                    @case('approved') <span class="text-green-600 font-bold text-xs uppercase">Approuvé</span> @break
                                    @case('rejected') <span class="text-red-600 font-bold text-xs uppercase">Refusé</span> @break
                                    @case('pending') <span class="text-yellow-600 font-bold text-xs uppercase">En attente</span> @break
                                    @default {{ $leave->status }}
                                @endswitch
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 italic">Aucune absence enregistrée.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
