@extends('layouts.app')
@section('title', 'Modifier Groupe - ' . $partnerGroup->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ activeTab: 'details' }">
    <!-- Header & Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <nav class="flex mb-1" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('partner-groups.index') }}" class="text-gray-500 hover:text-gray-700 font-medium text-sm">Groupes</a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                            <span class="ml-1 text-gray-400 font-medium text-sm md:ml-2">{{ $partnerGroup->name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-gray-900 tracking-tight">{{ $partnerGroup->name }}</h2>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('partner-groups.attendance', $partnerGroup->group_id) }}" class="inline-flex items-center px-4 py-2 border border-blue-200 shadow-sm text-sm font-medium rounded-lg text-blue-700 bg-blue-50 hover:bg-blue-100 transition-colors">
                <svg class="w-5 h-5 mr-2 -ml-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Historique
            </a>
            <a href="{{ route('partner-groups.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
                Retour
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Badges Actifs</p>
                <p class="text-2xl font-bold text-gray-900">{{ $partnerGroup->badges->where('status', 'active')->count() }}</p>
            </div>
            <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Créneaux / Semaine</p>
                <p class="text-2xl font-bold text-gray-900">{{ $partnerGroup->slots->count() }}</p>
            </div>
            <div class="p-3 bg-green-50 rounded-xl text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Capacité Totale Hebdo</p>
                <p class="text-2xl font-bold text-gray-900">{{ $partnerGroup->slots->sum('max_capacity') }}</p>
            </div>
            <div class="p-3 bg-orange-50 rounded-xl text-orange-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Details Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Group Information Form -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
                    <h3 class="text-lg font-semibold text-gray-900">Informations Générales</h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('partner-groups.update', $partnerGroup->group_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du groupe</label>
                                <input type="text" name="name" value="{{ old('name', $partnerGroup->name) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom du contact</label>
                                <input type="text" name="contact_name" value="{{ old('contact_name', $partnerGroup->contact_name) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                <input type="text" name="contact_phone" value="{{ old('contact_phone', $partnerGroup->contact_phone) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="email" value="{{ old('email', $partnerGroup->email) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">{{ old('notes', $partnerGroup->notes) }}</textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-sm text-sm font-medium">
                                Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subscription & Payment -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" x-data="{ showSubForm: {{ $partnerGroup->subscriptions->where('status', 'active')->isEmpty() ? 'true' : 'false' }} }">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Abonnement & Paiement</h3>
                    <button @click="showSubForm = !showSubForm" type="button" class="text-sm font-medium hover:text-blue-800 transition-colors" :class="showSubForm ? 'text-red-600' : 'text-blue-600'">
                        <span x-show="!showSubForm">+ Nouvel Abonnement</span>
                        <span x-show="showSubForm">Annuler</span>
                    </button>
                </div>
                <div class="p-6">
                    <!-- Current Active Subscription -->
                    @php 
                        $activeSub = $partnerGroup->subscriptions->where('status', 'active')->first(); 
                    @endphp

                    @if($activeSub)
                        <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-100">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-green-500 text-white uppercase tracking-wide">Actif</span>
                                        <h4 class="text-lg font-bold text-gray-900">{{ $activeSub->plan->plan_name ?? 'Plan Inconnu' }}</h4>
                                    </div>
                                    <p class="text-sm text-blue-800">
                                        Du <strong>{{ $activeSub->start_date->format('d/m/Y') }}</strong> 
                                        au <strong>{{ $activeSub->end_date->format('d/m/Y') }}</strong>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-blue-600 uppercase tracking-wide font-semibold">Validité</p>
                                    <p class="text-2xl font-bold text-blue-900">
                                        {{ $activeSub->end_date->diffInDays(now()) < 0 ? 'Expiré' : $activeSub->end_date->diffInDays(now()) . ' jours' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 p-4 rounded-xl bg-gray-50 border border-gray-100 text-center text-gray-500 text-sm">
                            Aucun abonnement actif pour ce groupe.
                        </div>
                    @endif

                    <!-- New Subscription Form -->
                    <div x-show="showSubForm" x-transition class="border-t border-gray-100 pt-6">
                        <form action="{{ route('partner-groups.subscription.store', $partnerGroup->group_id) }}" method="POST">
                            @csrf
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide mb-4">Création d'un nouvel abonnement</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Plan Tarifaire</label>
                                    <select name="plan_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->plan_id }}">{{ $plan->plan_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Activité</label>
                                    <select name="activity_id" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                        @foreach($activities as $activity)
                                            <option value="{{ $activity->activity_id }}">{{ $activity->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                                    <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+1 year')) }}" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                </div>

                                <div class="md:col-span-2 border-t border-gray-100 pt-4 mt-2">
                                    <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Paiement Initial</h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Montant Payé (DA)</label>
                                            <input type="number" name="amount" min="0" step="0.01" value="0.00" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm py-2 font-mono font-bold">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Mode de Paiement</label>
                                            <select name="payment_method" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                                <option value="cash">Espèces</option>
                                                <option value="card">Carte Bancaire</option>
                                                <option value="check">Chèque</option>
                                                <option value="transfer">Virement</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optionnel)</label>
                                            <input type="text" name="notes" placeholder="Réf. virement, n° chèque..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-sm text-sm font-bold flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Valider Abonnement & Paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Slots Management -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200" x-data="{ activeDay: 'Lundi' }">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Gestion des Créneaux</h3>
                        <p class="text-sm text-gray-500">Sélectionnez les créneaux autorisés pour ce groupe.</p>
                    </div>
                    
                    <!-- Global Capacity Input (used by quick add forms) -->
                    <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm" x-data="{ globalCapacity: 15 }">
                        <span class="text-xs font-medium text-gray-500 uppercase">Capacité par défaut</span>
                        <input type="number" x-model="globalCapacity" x-on:change="$dispatch('capacity-changed', globalCapacity)" class="w-16 border-0 p-0 text-center font-bold text-gray-700 focus:ring-0 text-sm" min="1">
                    </div>
                </div>

                <!-- Day Tabs -->
                <div class="border-b border-gray-100 overflow-x-auto">
                    <nav class="flex px-6 space-x-4" aria-label="Tabs">
                        @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $day)
                        <button 
                            @click="activeDay = '{{ $day }}'"
                            :class="activeDay === '{{ $day }}' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            {{ $day }}
                        </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-6 bg-gray-50 min-h-[300px]">
                    @php
                        // Group slots by day name (assuming weekday relationship exists and has 'day_name')
                        $slotsByDay = $timeSlots->groupBy(function($slot) {
                            return $slot->weekday->day_name; 
                        });
                        
                        // Create lookup of assigned slot IDs
                        $assignedSlotIds = $partnerGroup->slots->pluck('slot_id')->toArray();
                    @endphp

                    @foreach(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $day)
                    <div x-show="activeDay === '{{ $day }}'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" style="display: none;">
                        
                        @if(isset($slotsByDay[$day]) && $slotsByDay[$day]->isNotEmpty())
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($slotsByDay[$day] as $slot)
                                    @php
                                        $isAssigned = in_array($slot->slot_id, $assignedSlotIds);
                                        $assignedPivot = $partnerGroup->slots->where('slot_id', $slot->slot_id)->first();
                                    @endphp

                                    <div class="relative bg-white rounded-xl border {{ $isAssigned ? 'border-blue-200 ring-1 ring-blue-100' : 'border-gray-200 hover:border-blue-300' }} p-4 shadow-sm transition group">
                                        <!-- Header -->
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isAssigned ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $slot->activity->name ?? 'Activité' }}
                                            </span>
                                            @if($isAssigned)
                                                <span class="text-xs text-blue-600 font-bold bg-blue-50 px-2 py-0.5 rounded">Assigné</span>
                                            @endif
                                        </div>

                                        <!-- Time -->
                                        <div class="mb-4">
                                            <h4 class="text-xl font-bold text-gray-900">
                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}
                                                <span class="text-gray-400 font-normal text-base">-</span>
                                                {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                            </h4>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center justify-between mt-auto pt-3 border-t border-gray-50">
                                            @if($isAssigned)
                                                <div class="text-xs text-gray-500">
                                                    Max: <strong>{{ $assignedPivot->max_capacity }}</strong> pers.
                                                </div>
                                                <form action="{{ route('partner-groups.slots.remove', ['partnerGroup' => $partnerGroup->group_id, 'slot' => $assignedPivot->id]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold hover:underline">
                                                        Retirer
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('partner-groups.slots.add', $partnerGroup->group_id) }}" method="POST" class="w-full" x-data="{ capacity: 15 }" @capacity-changed.window="capacity = $event.detail">
                                                    @csrf
                                                    <input type="hidden" name="slot_id" value="{{ $slot->slot_id }}">
                                                    <input type="hidden" name="max_capacity" x-model="capacity">
                                                    
                                                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-blue-600 hover:text-white transition text-sm font-medium border border-gray-200 hover:border-blue-600 group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:border-blue-200">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                        Ajouter
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <p>Aucun créneau disponible pour {{ $day }}.</p>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar (Badge Management) -->
        <div class="space-y-8">
            <!-- Badges Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 sticky top-6">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl">
                    <h3 class="text-lg font-semibold text-gray-900">Badges d'accès</h3>
                </div>
                <div class="p-6">
                    <!-- Add Badge Form -->
                    <form action="{{ route('partner-groups.badges.add', $partnerGroup->group_id) }}" method="POST" class="mb-6">
                        @csrf
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-gray-700">Associer un nouveau badge</label>
                            
                            @if($availableBadges->isEmpty())
                                <div class="p-3 bg-yellow-50 text-yellow-700 text-sm rounded-lg border border-yellow-200">
                                    Aucun badge disponible. Veuillez d'abord créer des badges non assignés dans le système.
                                </div>
                            @else
                                <div class="flex gap-2">
                                    <select name="badge_uid" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">-- Choisir un badge --</option>
                                        @foreach($availableBadges as $badge)
                                            <option value="{{ $badge->badge_uid }}">
                                                Badge #{{ $badge->badge_uid }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition shadow-sm text-sm font-medium whitespace-nowrap">
                                        Associer
                                    </button>
                                </div>
                                <input type="hidden" name="status" value="active">
                            @endif
                        </div>
                    </form>

                    <!-- Badges List -->
                    <div class="space-y-3">
                        @forelse($partnerGroup->badges as $badge)
                        <div class="flex items-center justify-between p-3 border border-gray-100 rounded-lg {{ $badge->status === 'active' ? 'bg-white' : 'bg-gray-50' }}">
                            <div class="flex items-center gap-3">
                                <div class="{{ $badge->status === 'active' ? 'text-green-500' : 'text-gray-400' }}">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-gray-900 font-mono">{{ $badge->badge_uid }}</span>
                                    <span class="text-xs {{ $badge->status === 'active' ? 'text-green-600' : 'text-gray-500' }}">
                                        {{ ucfirst($badge->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <!-- Toggle Status -->
                                <form action="{{ route('partner-groups.badges.toggle', ['partnerGroup' => $partnerGroup->group_id, 'badge' => $badge->badge_id]) }}" method="POST">
                                    @csrf
                                    <button type="submit" title="{{ $badge->status === 'active' ? 'Désactiver' : 'Activer' }}" class="p-1.5 text-gray-400 hover:text-indigo-600 transition rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    </button>
                                </form>
                                <!-- Delete -->
                                <form action="{{ route('partner-groups.badges.remove', ['partnerGroup' => $partnerGroup->group_id, 'badge' => $badge->badge_id]) }}" method="POST" onsubmit="return confirm('Dissocier ce badge ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Dissocier" class="p-1.5 text-gray-400 hover:text-red-600 transition rounded-md ml-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-gray-500 text-sm italic">Aucun badge associé.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            @if (Auth::user()->role->role_name === 'admin')
            <div class="bg-red-50 rounded-xl border border-red-100 p-6">
                <h4 class="text-sm font-semibold text-red-800 mb-2">Zone Danger</h4>
                <p class="text-xs text-red-600 mb-4">La suppression du groupe est irréversible et supprimera tout l'historique associé.</p>
                <form action="{{ route('partner-groups.destroy', $partnerGroup->group_id) }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 border border-red-600 text-red-600 rounded-lg hover:bg-red-600 hover:text-white transition text-sm font-medium">
                        Supprimer le Groupe
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection