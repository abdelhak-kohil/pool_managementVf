@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6" x-data="{ activeTab: 'daily' }">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Tâches & Check-lists</h1>
            <p class="text-slate-500">Suivi quotidien, hebdomadaire et mensuel</p>
            <a href="{{ route('pool.tasks.templates.index') }}" class="text-sm text-blue-600 hover:underline mt-1 inline-block">Gérer les modèles</a>
        </div>
        
        <!-- Tabs -->
        <div class="flex bg-white rounded-lg p-1 shadow-sm border border-slate-200">
            <button @click="activeTab = 'daily'" 
                :class="activeTab === 'daily' ? 'bg-blue-100 text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Journalier
            </button>
            <button @click="activeTab = 'weekly'" 
                :class="activeTab === 'weekly' ? 'bg-purple-100 text-purple-700 shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Hebdomadaire
            </button>
            <button @click="activeTab = 'monthly'" 
                :class="activeTab === 'monthly' ? 'bg-orange-100 text-orange-700 shadow-sm' : 'text-slate-600 hover:bg-slate-50'"
                class="px-4 py-2 rounded-md text-sm font-medium transition-all">
                Mensuel
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    <!-- Daily Tasks -->
    <div x-show="activeTab === 'daily'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white rounded-xl shadow-sm p-6 lg:col-span-2">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-slate-800">Journalier - {{ now()->format('d/m/Y') }}</h2>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Quotidien</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($pools as $pool)
                        <div class="border border-slate-100 rounded-xl p-6 bg-slate-50">
                            <h3 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                                <span class="text-2xl">🏊</span>
                                {{ $pool->name }}
                            </h3>

                            @php
                                $dailyTask = $dailyTasks[$pool->facility_id] ?? null;
                            @endphp

                            @if($dailyTask)
                                <div class="bg-green-100 border border-green-200 rounded-lg p-4 mb-4">
                                    <div class="flex items-center gap-2 text-green-800 mb-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <span class="font-bold">Effectué par {{ $dailyTask->technician->name ?? 'Technicien' }}</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2 text-sm text-green-800">
                                        <span><span class="font-semibold">Skimmers:</span> {{ $dailyTask->skimmer_cleaned ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Fond:</span> {{ $dailyTask->vacuum_done ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Bondes:</span> {{ $dailyTask->drains_checked ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Éclairage:</span> {{ $dailyTask->lighting_checked ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Débris:</span> {{ $dailyTask->debris_removed ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Grilles:</span> {{ $dailyTask->drain_covers_inspected ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Clarté:</span> {{ $dailyTask->clarity_test_passed ? 'Oui' : 'Non' }}</span>
                                        <span><span class="font-semibold">Pression:</span> {{ $dailyTask->pressure_reading ?? '-' }} bar</span>
                                    </div>
                                </div>
                            @else
                                <form action="{{ route('pool.tasks.store-daily') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="pool_id" value="{{ $pool->facility_id }}">
                                    @if($dailyTemplate)
                                        <input type="hidden" name="template_id" value="{{ $dailyTemplate->id }}">
                                        
                                        <div class="grid grid-cols-2 gap-3">
                                            @foreach($dailyTemplate->items as $item)
                                                @if($item['type'] === 'checkbox')
                                                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-white transition">
                                                        <input type="checkbox" name="{{ $item['key'] }}" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                        <span class="text-sm text-slate-700">{{ $item['label'] }}</span>
                                                    </label>
                                                @endif
                                            @endforeach
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-200">
                                            @foreach($dailyTemplate->items as $item)
                                                @if($item['type'] === 'number')
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                        <input type="number" name="{{ $item['key'] }}" step="{{ $item['step'] ?? '1' }}" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                                                    </div>
                                                @elseif($item['type'] === 'select')
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                        <select name="{{ $item['key'] }}" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                                                            @if(isset($item['options']) && is_array($item['options']))
                                                                @foreach($item['options'] as $val => $label)
                                                                    <option value="{{ $val }}">{{ $label }}</option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>

                                        @foreach($dailyTemplate->items as $item)
                                            @if($item['type'] === 'textarea')
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                    <textarea name="{{ $item['key'] }}" rows="2" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500"></textarea>
                                                </div>
                                            @elseif($item['type'] === 'text')
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                    <input type="text" name="{{ $item['key'] }}" class="w-full text-sm rounded border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <!-- Fallback if no template active -->
                                        <div class="text-sm text-red-500">Aucun modèle actif trouvé.</div>
                                    @endif

                                    <div class="flex items-center justify-between pt-2">
                                        <a href="{{ route('pool.water-tests.create') }}" class="text-xs text-blue-600 hover:underline">
                                            + Ajouter Test Eau (pH/Cl)
                                        </a>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 transition shadow-sm">
                                            Valider
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Tasks -->
    <div x-show="activeTab === 'weekly'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800">Hebdomadaire - Semaine {{ now()->weekOfYear }}</h2>
                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Hebdo</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($pools as $pool)
                    <div class="border border-slate-100 rounded-xl p-6 bg-slate-50">
                        <h3 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                            <span class="text-2xl">🗓️</span>
                            {{ $pool->name }}
                        </h3>

                        @php
                            $weeklyTask = $weeklyTasks[$pool->facility_id] ?? null;
                        @endphp

                        @if($weeklyTask)
                            <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 mb-4">
                                <div class="flex items-center gap-2 text-purple-700 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">Validé par {{ $weeklyTask->technician->name ?? 'Technicien' }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm text-purple-800">
                                    <span><span class="font-semibold">Backwash:</span> {{ $weeklyTask->backwash_done ? 'Oui' : 'Non' }}</span>
                                    <span><span class="font-semibold">Préfiltre:</span> {{ $weeklyTask->filter_cleaned ? 'Oui' : 'Non' }}</span>
                                    <span><span class="font-semibold">Brossage:</span> {{ $weeklyTask->brushing_done ? 'Oui' : 'Non' }}</span>
                                    <span><span class="font-semibold">Raccords:</span> {{ $weeklyTask->fittings_retightened ? 'Oui' : 'Non' }}</span>
                                    <span><span class="font-semibold">Chauffage:</span> {{ $weeklyTask->heater_tested ? 'Oui' : 'Non' }}</span>
                                    <span><span class="font-semibold">Doseuse:</span> {{ $weeklyTask->chemical_doser_checked ? 'Oui' : 'Non' }}</span>
                                </div>
                            </div>
                        @else
                            <form action="{{ route('pool.tasks.store-weekly') }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="pool_id" value="{{ $pool->facility_id }}">
                                @if($weeklyTemplate)
                                    <input type="hidden" name="template_id" value="{{ $weeklyTemplate->id }}">
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($weeklyTemplate->items as $item)
                                            @if($item['type'] === 'checkbox')
                                                <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-white transition">
                                                    <input type="checkbox" name="{{ $item['key'] }}" value="1" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                                    <span class="text-sm text-slate-700">{{ $item['label'] }}</span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>

                                    @foreach($weeklyTemplate->items as $item)
                                        @if($item['type'] === 'textarea')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                <textarea name="{{ $item['key'] }}" rows="2" class="w-full text-sm rounded border-slate-200 focus:border-purple-500 focus:ring-purple-500"></textarea>
                                            </div>
                                        @elseif($item['type'] === 'select')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                <select name="{{ $item['key'] }}" class="w-full text-sm rounded border-slate-200 focus:border-purple-500 focus:ring-purple-500">
                                                    @if(isset($item['options']) && is_array($item['options']))
                                                        @foreach($item['options'] as $val => $label)
                                                            <option value="{{ $val }}">{{ $label }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-sm text-red-500">Aucun modèle actif trouvé.</div>
                                @endif

                                <button type="submit" class="w-full py-2 bg-purple-600 text-white text-sm font-medium rounded hover:bg-purple-700 transition shadow-sm">
                                    Valider Hebdo
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Monthly Tasks -->
    <div x-show="activeTab === 'monthly'" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800">Mensuel - {{ now()->format('F Y') }}</h2>
                <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Mensuel</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($pools as $pool)
                    <div class="border border-slate-100 rounded-xl p-6 bg-slate-50">
                        <h3 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                            <span class="text-2xl">📅</span>
                            {{ $pool->name }}
                        </h3>

                        @php
                            $monthlyTask = $monthlyTasks[$pool->facility_id] ?? null;
                        @endphp

                        @if($monthlyTask)
                            <div class="bg-orange-50 border border-orange-100 rounded-lg p-4 mb-4">
                                <div class="flex items-center gap-2 text-orange-700 mb-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="font-medium">Validé par {{ $monthlyTask->technician->name ?? 'Technicien' }}</span>
                                </div>
                                <div class="space-y-1 text-sm text-orange-800">
                                    <div class="flex justify-between">
                                        <span>Remplacement Eau (Partiel):</span>
                                        <span class="font-semibold">{{ $monthlyTask->water_replacement_partial ? 'Oui' : 'Non' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Inspection Système Complet:</span>
                                        <span class="font-semibold">{{ $monthlyTask->full_system_inspection ? 'Oui' : 'Non' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Calibration Dosage Chimique:</span>
                                        <span class="font-semibold">{{ $monthlyTask->chemical_dosing_calibration ? 'Oui' : 'Non' }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <form action="{{ route('pool.tasks.store-monthly') }}" method="POST" class="space-y-4">
                                @csrf
                                <input type="hidden" name="facility_id" value="{{ $pool->facility_id }}">
                                @if($monthlyTemplate)
                                    <input type="hidden" name="template_id" value="{{ $monthlyTemplate->id }}">
                                    
                                    <div class="space-y-3">
                                        @foreach($monthlyTemplate->items as $item)
                                            @if($item['type'] === 'checkbox')
                                                <label class="flex items-center gap-2 cursor-pointer p-2 rounded hover:bg-white transition">
                                                    <input type="checkbox" name="{{ $item['key'] }}" value="1" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                                                    <span class="text-sm text-slate-700">{{ $item['label'] }}</span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>

                                    @foreach($monthlyTemplate->items as $item)
                                        @if($item['type'] === 'textarea')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                <textarea name="{{ $item['key'] }}" rows="2" class="w-full text-sm rounded border-slate-200 focus:border-orange-500 focus:ring-orange-500"></textarea>
                                            </div>
                                        @elseif($item['type'] === 'select')
                                            <div>
                                                <label class="block text-xs font-medium text-slate-500 mb-1">{{ $item['label'] }}</label>
                                                <select name="{{ $item['key'] }}" class="w-full text-sm rounded border-slate-200 focus:border-orange-500 focus:ring-orange-500">
                                                    @if(isset($item['options']) && is_array($item['options']))
                                                        @foreach($item['options'] as $val => $label)
                                                            <option value="{{ $val }}">{{ $label }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        @endif
                                    @endforeach
                                @else
                                    <div class="text-sm text-red-500">Aucun modèle actif trouvé.</div>
                                @endif

                                <button type="submit" class="w-full py-2 bg-orange-600 text-white text-sm font-medium rounded hover:bg-orange-700 transition shadow-sm">
                                    Valider Mensuel
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
