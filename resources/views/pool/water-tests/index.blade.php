@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Analyses d'Eau</h1>
            <p class="text-slate-500">Historique des relevés de qualité</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('pool.water-tests.export-pdf', request()->all()) }}" class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-700 shadow-sm hover:bg-slate-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Export PDF
            </a>
            <a href="{{ route('pool.water-tests.create') }}" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Relevé
            </a>
        </div>
    </div>

    <!-- Filters & Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Filter Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 mb-4">Filtrer par Bassin</h3>
            <form method="GET" action="{{ route('pool.water-tests.index') }}">
                <div class="flex gap-2">
                    <select name="pool_id" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" onchange="this.form.submit()">
                        <option value="">Tous les bassins</option>
                        @foreach($pools as $pool)
                            <option value="{{ $pool->facility_id }}" {{ request('pool_id') == $pool->facility_id ? 'selected' : '' }}>
                                {{ $pool->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <!-- Chart Card -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border border-slate-100">
            <h3 class="font-bold text-slate-800 mb-4">Évolution pH / Chlore</h3>
            <div class="h-64">
                <canvas id="waterQualityChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('waterQualityChart').getContext('2d');
            const poolId = "{{ request('pool_id', 'all') }}"; // Default to 'all' if empty

            fetch(`{{ url('pool/water-tests/history') }}/${poolId}`)
                .then(response => response.json())
                .then(response => {
                    let datasets = [];
                    let labels = [];

                    if (response.type === 'single') {
                        const data = response.data;
                        labels = data.map(item => new Date(item.test_date).toLocaleDateString('fr-FR'));
                        datasets = [
                            {
                                label: 'pH',
                                data: data.map(item => item.ph),
                                borderColor: 'rgb(234, 179, 8)', // Yellow-500
                                backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                yAxisID: 'y',
                                tension: 0.3
                            },
                            {
                                label: 'Chlore Libre (ppm)',
                                data: data.map(item => item.chlorine_free),
                                borderColor: 'rgb(59, 130, 246)', // Blue-500
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                yAxisID: 'y1',
                                tension: 0.3
                            }
                        ];
                    } else if (response.type === 'multiple') {
                        // Collect all unique dates for labels
                        let allDates = new Set();
                        Object.values(response.datasets).forEach(pool => {
                            pool.data.forEach(item => allDates.add(new Date(item.test_date).toLocaleDateString('fr-FR')));
                        });
                        labels = Array.from(allDates).sort((a, b) => new Date(a.split('/').reverse().join('-')) - new Date(b.split('/').reverse().join('-')));

                        // Create datasets for each pool (pH only for clarity, or both if needed)
                        // Using a color palette
                        const colors = [
                            'rgb(59, 130, 246)', 'rgb(234, 179, 8)', 'rgb(16, 185, 129)', 'rgb(239, 68, 68)', 
                            'rgb(139, 92, 246)', 'rgb(249, 115, 22)', 'rgb(236, 72, 153)', 'rgb(99, 102, 241)'
                        ];
                        
                        let colorIndex = 0;
                        Object.values(response.datasets).forEach(pool => {
                            // Map data to the common labels to ensure alignment
                            const poolData = labels.map(date => {
                                const found = pool.data.find(d => new Date(d.test_date).toLocaleDateString('fr-FR') === date);
                                return found ? found.ph : null;
                            });

                            datasets.push({
                                label: `${pool.name} (pH)`,
                                data: poolData,
                                borderColor: colors[colorIndex % colors.length],
                                backgroundColor: colors[colorIndex % colors.length],
                                yAxisID: 'y',
                                tension: 0.3,
                                spanGaps: true
                            });
                            colorIndex++;
                        });
                    }

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                tooltip: {
                                    mode: 'index',
                                    intersect: false
                                }
                            },
                            scales: {
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    title: { display: true, text: 'pH' },
                                    min: 6.5,
                                    max: 8.5
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    title: { display: true, text: 'Chlore (ppm)' },
                                    grid: {
                                        drawOnChartArea: false,
                                    },
                                    min: 0,
                                    max: 5,
                                    display: response.type === 'single' // Only show Chlorine axis for single pool view
                                }
                            }
                        }
                    });
                });
        });
    </script>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-6 rounded-lg bg-orange-100 p-4 text-orange-700 border border-orange-200">
            {{ session('warning') }}
        </div>
    @endif

    <div class="rounded-xl bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-4 font-semibold">Date</th>
                        <th class="px-6 py-4 font-semibold">Bassin</th>
                        <th class="px-6 py-4 font-semibold">pH</th>
                        <th class="px-6 py-4 font-semibold">Chlore Libre</th>
                        <th class="px-6 py-4 font-semibold">Température</th>
                        <th class="px-6 py-4 font-semibold">Technicien</th>
                        <th class="px-6 py-4 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tests as $test)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-medium text-slate-900">
                                {{ $test->test_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $test->pool->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($test->ph < 7.2 || $test->ph > 7.8) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ $test->ph }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                {{ $test->chlorine_free }} ppm
                            </td>
                            <td class="px-6 py-4">
                                {{ $test->temperature }}°C
                            </td>
                            <td class="px-6 py-4">
                                {{ $test->technician->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('pool.water-tests.show', $test) }}" class="text-blue-600 hover:text-blue-900 font-medium">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                Aucun relevé trouvé.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100">
            {{ $tests->links() }}
        </div>
    </div>
</div>
@endsection
