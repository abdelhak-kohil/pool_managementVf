@extends('layouts.app')
@section('title', 'Profil Membre')

@section('content')
<div class="max-w-[1200px] mx-auto px-4 sm:px-6 lg:px-8 py-8 animate-fade-in-up">

    <!-- HEADER & ACTIONS -->
    <div class="flex items-center justify-between mb-8">
        <div>
           <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Profil Membre
                </span>
            </h1>
            <p class="mt-2 text-sm text-gray-500">Vue détaillée des informations, abonnements et historique.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('members.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Retour
            </a>
            <a href="{{ route('members.edit', $member->member_id) }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 shadow-lg shadow-blue-200 hover:shadow-xl hover:scale-105 transition-all duration-200">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                Modifier
            </a>
        </div>
    </div>

    <!-- MAIN PROFILE CARD -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-8">
            <div class="flex flex-col md:flex-row gap-10">
                
                <!-- PHOTO & BADGE STATUS -->
                <div class="flex flex-col items-center space-y-6 min-w-[200px]">
                    <div class="w-48 h-48 rounded-full bg-gray-50 border-[6px] border-white shadow-xl overflow-hidden relative group">
                        @if($member->photo_path)
                            <img src="{{ Storage::url($member->photo_path) }}" alt="Photo" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-50 text-blue-300">
                                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                        @endif
                    </div>
                    
                    @php
                        $status = $member->accessBadge->status ?? 'inactif';
                        $statusClasses = [
                            'active' => 'bg-green-100 text-green-700 border-green-200',
                            'inactive' => 'bg-gray-100 text-gray-700 border-gray-200',
                            'lost' => 'bg-orange-100 text-orange-700 border-orange-200',
                            'blocked' => 'bg-red-100 text-red-700 border-red-200',
                        ];
                        $badges = [
                            'active' => '<svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
                            'inactive' => '',
                            'lost' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                            'blocked' => '<svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        ];
                    @endphp
                    <div class="text-center w-full">
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border {{ $statusClasses[$status] ?? $statusClasses['inactive'] }}">
                            {!! $badges[$status] ?? '' !!}
                            {{ ucfirst($status) }}
                        </div>
                        <div class="mt-2 flex items-center justify-center gap-1 text-xs text-gray-500 font-mono bg-gray-50 rounded-lg py-1 px-3 border border-gray-100 inline-block">
                             <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                             {{ $member->accessBadge->badge_uid ?? '—' }}
                        </div>
                    </div>
                </div>

                <!-- INFO GRID -->
                <div class="flex-1 grid md:grid-cols-2 gap-x-12 gap-y-8">
                    
                    <!-- Identity -->
                    <div>
                        <h3 class="flex items-center text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Identité
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <p class="text-3xl font-bold text-gray-900 leading-tight">{{ $member->first_name }} {{ $member->last_name }}</p>
                                <p class="text-sm text-gray-500 mt-1 flex items-center gap-1">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    Inscrit le {{ $member->created_at ? $member->created_at->format('d/m/Y') : 'N/A' }}
                                </p>
                            </div>
                            
                             <div class="grid grid-cols-1 gap-3">
                                <div class="flex items-center gap-3 text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-transparent hover:border-gray-200 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.718 2.718 0 00-.917-.064 2.1 2.1 0 00-1.833-1.353c-.563 0-1.126.176-1.603.528A.886.886 0 0014.17 14c-.642 0-1.284.288-1.55.862a1.693 1.693 0 00-.233.136c-.255.19-.508.384-.761.574-.755.566-1.51 1.133-2.227 1.769A3.37 3.37 0 018.5 18H5a1 1 0 110-2h3.5c.34 0 .68-.057 1.01-.17l1.04-.368A6.98 6.98 0 0013.29 11h.06a3.89 3.89 0 000-1H11.2a8.6 8.6 0 01-1.99-3.41c-.26-.7-.61-1.37-1.05-1.99a2.008 2.008 0 00-3.32 0C4.4 5.23 4.05 5.9 3.79 6.6 3.25 8.04 3 9.53 3 11h2c0-1.26.21-2.52.68-3.75.05-.12.1-.25.16-.37a8.67 8.67 0 012.3-3.08c1.32-.97 2.9-.84 4.07-.09.34.22.69.43 1.05.62V5a1 1 0 102 0v.1c.36-.19.71-.4 1.05-.62 1.17-.75 2.75-.88 4.07.09.84.62 1.62 1.34 2.3 2.19.26.31.5.64.72.98.67 1.05 1.08 2.27 1.19 3.51.01.12.01.25.01.37l-1.99-.02ZM12 4a3 3 0 110 6 3 3 0 010-6zm3 4a1 1 0 100 2 1 1 0 000-2z"></path></svg>
                                    </div>
                                    <span class="font-medium">{{ $member->date_of_birth ? $member->date_of_birth->format('d/m/Y') . ' (' . $member->date_of_birth->age . ' ans)' : 'Non renseigné' }}</span>
                                </div>
                                <div class="flex items-center gap-3 text-gray-700 bg-gray-50 p-2.5 rounded-lg border border-transparent hover:border-gray-200 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                         <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </div>
                                    <span class="font-medium">{{ $member->address ?? 'Aucune adresse' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h3 class="flex items-center text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">
                            Coordonnées
                        </h3>
                        <div class="space-y-3">
                             <div class="flex items-center">
                                <span class="w-32 text-sm text-gray-500 flex items-center gap-2">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    Téléphone
                                </span>
                                <a href="tel:{{ $member->phone_number }}" class="text-gray-900 font-medium hover:text-blue-600 transition">{{ $member->phone_number ?? '—' }}</a>
                            </div>
                            <div class="flex items-center">
                                <span class="w-32 text-sm text-gray-500 flex items-center gap-2">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    Email
                                </span>
                                <a href="mailto:{{ $member->email }}" class="text-gray-900 font-medium hover:text-blue-600 transition">{{ $member->email ?? '—' }}</a>
                            </div>

                            @if($member->emergency_contact_name)
                            <div class="mt-4 p-4 bg-red-50 rounded-xl border border-red-100 flex items-start gap-3">
                                <div class="p-2 bg-white rounded-lg text-red-500 shadow-sm">
                                     <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-red-800 uppercase tracking-wide mb-1">Contact d'urgence</h4>
                                    <p class="text-sm font-bold text-gray-900">{{ $member->emergency_contact_name }}</p>
                                    <p class="text-sm text-red-600">{{ $member->emergency_contact_phone }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Additional Info -->
                    @if($member->notes || $member->health_conditions)
                    <div class="md:col-span-2 grid md:grid-cols-2 gap-8 pt-6 border-t border-gray-100 mt-2">
                        @if($member->notes)
                        <div>
                            <h3 class="flex items-center text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                Notes Internes
                            </h3>
                            <div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 p-4 rounded-xl italic">
                                "{{ $member->notes }}"
                            </div>
                        </div>
                        @endif
                        
                        @if($member->health_conditions)
                        <div>
                             <h3 class="flex items-center text-xs font-bold text-orange-400 uppercase tracking-wider mb-2 gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                Santé / Allergies
                            </h3>
                            <div class="text-sm text-orange-900 bg-orange-50 border border-orange-200 p-4 rounded-xl font-medium">
                                {{ $member->health_conditions }}
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <!-- TABS: HISTORY & LOGS -->
    <div x-data="{ tab: 'subscriptions' }" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden min-h-[400px]">
        
        <!-- Tab Headers -->
        <div class="flex border-b border-gray-100 bg-gray-50/50 overflow-x-auto hide-scrollbar">
             <button @click="tab = 'subscriptions'" 
                :class="{ 'text-blue-600 bg-white border-b-2 border-blue-600 shadow-sm': tab === 'subscriptions', 'text-gray-500 hover:text-gray-700 hover:bg-gray-100': tab !== 'subscriptions' }"
                class="px-8 py-4 font-semibold text-sm focus:outline-none transition-all whitespace-nowrap flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                Abonnements
            </button>
            <button @click="tab = 'payments'" 
                :class="{ 'text-blue-600 bg-white border-b-2 border-blue-600 shadow-sm': tab === 'payments', 'text-gray-500 hover:text-gray-700 hover:bg-gray-100': tab !== 'payments' }"
                class="px-8 py-4 font-semibold text-sm focus:outline-none transition-all whitespace-nowrap flex items-center gap-2">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Paiements
            </button>
             <button @click="tab = 'sessions'" 
                :class="{ 'text-blue-600 bg-white border-b-2 border-blue-600 shadow-sm': tab === 'sessions', 'text-gray-500 hover:text-gray-700 hover:bg-gray-100': tab !== 'sessions' }"
                class="px-8 py-4 font-semibold text-sm focus:outline-none transition-all whitespace-nowrap flex items-center gap-2">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                Sessions Suivies
            </button>
             <button @click="tab = 'reservations'" 
                :class="{ 'text-blue-600 bg-white border-b-2 border-blue-600 shadow-sm': tab === 'reservations', 'text-gray-500 hover:text-gray-700 hover:bg-gray-100': tab !== 'reservations' }"
                class="px-8 py-4 font-semibold text-sm focus:outline-none transition-all whitespace-nowrap flex items-center gap-2">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Réservations
            </button>
        </div>

        <!-- Contents -->
        <div class="p-8 bg-white min-h-[300px]">
            
            <!-- 1. SUBSCRIPTIONS -->
            <div x-show="tab === 'subscriptions'" x-transition.opacity>
                @if($member->subscriptions->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <p class="text-sm">Aucun abonnement trouvé.</p>
                    </div>
                @else
                   <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs font-bold text-gray-500 uppercase border-b border-gray-100">
                                <th class="py-3 px-2">Plan</th>
                                <th class="py-3 px-2">Période</th>
                                <th class="py-3 px-2 text-center">Visites/Sem</th>
                                <th class="py-3 px-2">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($member->subscriptions as $sub)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="py-4 px-2">
                                    <div class="font-bold text-gray-900">{{ $sub->plan->plan_name }}</div>
                                    <div class="text-xs text-gray-500">{{ ucfirst($sub->plan->plan_type) }}</div>
                                </td>
                                <td class="py-4 px-2 text-sm text-gray-600">
                                    {{ $sub->start_date->format('d/m/Y') }} — {{ $sub->end_date->format('d/m/Y') }}
                                </td>
                                <td class="py-4 px-2 text-center text-sm">
                                    @if($sub->visits_per_week)
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-50 text-blue-600 font-bold text-xs">{{ $sub->visits_per_week }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="py-4 px-2">
                                     @php
                                        $subColors = [
                                            'active' => 'bg-green-50 text-green-700 border-green-100',
                                            'paused' => 'bg-yellow-50 text-yellow-700 border-yellow-100',
                                            'expired' => 'bg-gray-50 text-gray-500 border-gray-100',
                                            'cancelled' => 'bg-red-50 text-red-700 border-red-100',
                                        ];
                                     @endphp
                                     <span class="px-2.5 py-1 rounded-md text-xs font-bold border {{ $subColors[$sub->status] ?? 'bg-gray-50' }}">
                                         {{ ucfirst($sub->status) }}
                                     </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                   </table>
                @endif
            </div>

            <!-- 2. PAYMENTS -->
             <div x-show="tab === 'payments'" x-transition.opacity style="display: none;">
                @php $payments = $member->subscriptions->flatMap->payments; @endphp
                @if($payments->isEmpty())
                     <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="text-sm">Aucun paiement enregistré.</p>
                    </div>
                @else
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs font-bold text-gray-500 uppercase border-b border-gray-100">
                                <th class="py-3 px-2">Date</th>
                                <th class="py-3 px-2">Montant</th>
                                <th class="py-3 px-2">Méthode</th>
                                <th class="py-3 px-2">Reçu par</th>
                            </tr>
                        </thead>
                         <tbody class="divide-y divide-gray-50">
                             @foreach($payments as $p)
                             <tr class="hover:bg-gray-50/50">
                                 <td class="py-4 px-2 text-sm text-gray-600">{{ $p->payment_date->format('d/m/Y H:i') }}</td>
                                 <td class="py-4 px-2 bg-gray-50/50 font-mono font-semibold text-gray-900">{{ number_format($p->amount, 2) }} DZD</td>
                                 <td class="py-4 px-2 text-sm capitalize">{{ $p->payment_method }}</td>
                                 <td class="py-4 px-2 text-sm text-gray-500">{{ $p->staff->first_name ?? 'Système' }}</td>
                             </tr>
                             @endforeach
                         </tbody>
                    </table>
                @endif
             </div>

             <!-- 3. SESSIONS -->
              <div x-show="tab === 'sessions'" x-transition.opacity style="display: none;">
                   @if($member->accessLogs->isEmpty())
                     <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        <p class="text-sm">Aucun historique de passage.</p>
                    </div>
                   @else
                     <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-xs font-bold text-gray-500 uppercase border-b border-gray-100">
                                <th class="py-3 px-2">Date & Heure</th>
                                <th class="py-3 px-2">Activité</th>
                                <th class="py-3 px-2">Créneau</th>
                                <th class="py-3 px-2">Résultat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($member->accessLogs as $log)
                            <tr class="hover:bg-gray-50/50">
                                <td class="py-4 px-2 text-sm text-gray-900 font-medium">{{ $log->access_time->format('d/m/Y H:i') }}</td>
                                <td class="py-4 px-2 text-sm text-gray-600">{{ $log->activity->name ?? 'Unknown' }}</td>
                                <td class="py-4 px-2 text-sm text-gray-500">
                                    @if($log->slot)
                                        {{ \Carbon\Carbon::parse($log->slot->start_time)->format('H:i') }}
                                    @else
                                        <span class="italic text-gray-300">Hors-créneau</span>
                                    @endif
                                </td>
                                <td class="py-4 px-2">
                                     @if($log->access_decision === 'granted')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">
                                            ✅ Autorisé
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700" title="{{ $log->denial_reason }}">
                                            🚫 Refusé
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                     </table>
                   @endif
              </div>

               <!-- 4. RESERVATIONS -->
               <div x-show="tab === 'reservations'" x-transition.opacity style="display: none;">
                    @if($reservations->isEmpty())
                         <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-sm">Aucune réservation future.</p>
                        </div>
                    @else
                         <table class="w-full text-left border-collapse">
                             <!-- ... table similar to logs ... -->
                             @foreach($reservations as $res)
                             <tr class="hover:bg-gray-50/50">
                                 <td class="py-4 px-2 font-medium">{{ \Carbon\Carbon::parse($res->reserved_at)->format('d/m/Y') }}</td>
                                 <td class="py-4 px-2">{{ $res->activity_name }}</td>
                                 <td class="py-4 px-2">
                                    <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-700">
                                        {{ $res->status }}
                                    </span>
                                 </td>
                             </tr>
                             @endforeach
                         </table>
                    @endif
               </div>

        </div>
    </div>

</div>

<style>
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    @keyframes fade-in-up {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in-up { animation: fade-in-up 0.5s ease-out; }
</style>
@endsection
