@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Tableau de Bord Piscine</h1>
            <p class="text-slate-500">Vue d'ensemble des opérations et de la maintenance</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('pool.water-tests.create') }}" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                </svg>
                Nouveau Test Eau
            </a>
            <a href="{{ route('pool.incidents.create') }}" class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-white shadow hover:bg-red-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Signaler Incident
            </a>
        </div>
    </div>

    <!-- Widgets Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4 mb-8">
        
        <!-- Water Quality Widget -->
        <div class="rounded-xl bg-white p-6 shadow-sm border-l-4 {{ $latestTest && ($latestTest->ph < 7.2 || $latestTest->ph > 7.8) ? 'border-red-500' : 'border-emerald-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-700">Qualité de l'Eau</h3>
                <span class="rounded-full px-2 py-1 text-xs font-medium {{ $latestTest && ($latestTest->ph < 7.2 || $latestTest->ph > 7.8) ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $latestTest ? 'Mesuré le ' . $latestTest->test_date->format('d/m H:i') : 'Aucune donnée' }}
                </span>
            </div>
            @if($latestTest)
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">pH</span>
                        <span class="font-bold {{ ($latestTest->ph < 7.2 || $latestTest->ph > 7.8) ? 'text-red-600' : 'text-slate-800' }}">{{ $latestTest->ph }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Chlore Libre</span>
                        <span class="font-bold text-slate-800">{{ $latestTest->chlorine_free }} ppm</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-500">Température</span>
                        <span class="font-bold text-slate-800">{{ $latestTest->temperature }}°C</span>
                    </div>
                </div>
            @else
                <p class="text-slate-400 text-sm">En attente de relevés...</p>
            @endif
        </div>

        <!-- Incidents Widget -->
        <div class="rounded-xl bg-white p-6 shadow-sm border-l-4 {{ $pendingIncidents > 0 ? 'border-orange-500' : 'border-blue-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-700">Incidents</h3>
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-4xl font-bold text-slate-800">{{ $pendingIncidents }}</span>
                <p class="text-sm text-slate-500 mt-1">Incidents en cours</p>
            </div>
            <a href="{{ route('pool.incidents.index') }}" class="mt-4 block text-sm font-medium text-blue-600 hover:text-blue-800">Voir les détails &rarr;</a>
        </div>

        <!-- Maintenance Widget -->
        <div class="rounded-xl bg-white p-6 shadow-sm border-l-4 border-indigo-500">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-700">Maintenance</h3>
                <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-2">
                <span class="text-4xl font-bold text-slate-800">{{ $maintenanceToday }}</span>
                <p class="text-sm text-slate-500 mt-1">Tâches prévues aujourd'hui</p>
            </div>
            <a href="{{ route('pool.maintenance.index') }}" class="mt-4 block text-sm font-medium text-blue-600 hover:text-blue-800">Voir le planning &rarr;</a>
        </div>

        <!-- Chemical Stock Widget -->
        <div class="rounded-xl bg-white p-6 shadow-sm border-l-4 {{ $lowStockChemicals->count() > 0 ? 'border-yellow-500' : 'border-cyan-500' }}">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-slate-700">Stock Produits</h3>
                <div class="p-2 bg-cyan-50 rounded-lg text-cyan-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
            </div>
            @if($lowStockChemicals->count() > 0)
                <div class="space-y-2">
                    @foreach($lowStockChemicals->take(3) as $chem)
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-slate-600">{{ $chem->name }}</span>
                            <span class="font-bold text-red-600">{{ $chem->quantity_available }} {{ $chem->unit }}</span>
                        </div>
                    @endforeach
                    @if($lowStockChemicals->count() > 3)
                        <p class="text-xs text-slate-400 mt-1">+ {{ $lowStockChemicals->count() - 3 }} autres</p>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-2 text-emerald-600 mt-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-medium">Stocks OK</span>
                </div>
            @endif
            <a href="{{ route('pool.chemicals.index') }}" class="mt-4 block text-sm font-medium text-blue-600 hover:text-blue-800">Gérer les stocks &rarr;</a>
        </div>
    </div>

    <!-- Quick Actions & Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Actions Rapides</h3>
                <div class="space-y-3">
                    <a href="{{ route('pool.tasks.index') }}" class="block w-full p-4 rounded-lg border border-slate-200 hover:border-blue-500 hover:bg-blue-50 transition group">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-100 p-2 rounded-lg text-blue-600 group-hover:bg-blue-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-700">Check-list Quotidienne</h4>
                                <p class="text-xs text-slate-500">Remplir le relevé journalier</p>
                            </div>
                        </div>
                    </a>
                    
                    <a href="{{ route('pool.chemicals.index') }}" class="block w-full p-4 rounded-lg border border-slate-200 hover:border-cyan-500 hover:bg-cyan-50 transition group">
                        <div class="flex items-center gap-3">
                            <div class="bg-cyan-100 p-2 rounded-lg text-cyan-600 group-hover:bg-cyan-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-700">Consommation Produits</h4>
                                <p class="text-xs text-slate-500">Enregistrer un ajout de chimie</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('pool.equipment.index') }}" class="block w-full p-4 rounded-lg border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50 transition group">
                        <div class="flex items-center gap-3">
                            <div class="bg-indigo-100 p-2 rounded-lg text-indigo-600 group-hover:bg-indigo-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-700">Équipements</h4>
                                <p class="text-xs text-slate-500">État des pompes et filtres</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Évolution des Paramètres (3 mois)</h3>
            <div class="relative h-80 w-full">
                <canvas id="waterQualityChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('waterQualityChart').getContext('2d');
        
        const chartData = @json($chartData);

        if (!chartData.dates || chartData.dates.length === 0) {
            ctx.font = "16px Inter";
            ctx.fillStyle = "#64748b";
            ctx.textAlign = "center";
            ctx.fillText("Aucune donnée disponible pour la période.", ctx.canvas.width / 2, ctx.canvas.height / 2);
            return;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.dates,
                datasets: [
                    {
                        label: 'pH',
                        data: chartData.ph,
                        borderColor: '#0ea5e9', // Sky 500
                        backgroundColor: '#0ea5e9',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y',
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Chlore Libre (ppm)',
                        data: chartData.chlorine,
                        borderColor: '#10b981', // Emerald 500
                        backgroundColor: '#10b981',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y1',
                        pointRadius: 0,
                        pointHoverRadius: 4
                    },
                    {
                        label: 'Température (°C)',
                        data: chartData.temperature,
                        borderColor: '#f59e0b', // Amber 500
                        backgroundColor: '#f59e0b',
                        borderWidth: 2,
                        tension: 0.3,
                        yAxisID: 'y2',
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        hidden: true // Hidden by default to avoid clutter
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#1e293b',
                        bodyColor: '#475569',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: true,
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 12
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'pH'
                        },
                        min: 6.5,
                        max: 8.5
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Chlore (ppm)'
                        },
                        min: 0,
                        max: 5,
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                    y2: {
                        type: 'linear',
                        display: false, // Hidden axis but data exists
                        position: 'right',
                        min: 15,
                        max: 35,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    });
</script>
@endsection
