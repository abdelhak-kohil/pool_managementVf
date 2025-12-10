@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.facilities.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux bassins</a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">{{ $facility->name }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $facility->status == 'open' ? 'bg-green-100 text-green-700' : ($facility->status == 'maintenance' ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">
                        {{ ucfirst($facility->status) }}
                    </span>
                    <span class="text-slate-500 text-sm">{{ ucfirst(str_replace('_', ' ', $facility->type)) }}</span>
                </div>
            </div>
            <a href="{{ route('pool.facilities.edit', $facility) }}" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-medium">
                Modifier
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Info Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Caractéristiques</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Volume</p>
                        <p class="font-medium text-slate-700">{{ number_format($facility->volume_liters, 0, ',', ' ') }} Litres</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Plage Température</p>
                        <p class="font-medium text-slate-700">{{ $facility->min_temperature }} - {{ $facility->max_temperature }}°C</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Capacité</p>
                        <p class="font-medium text-slate-700">{{ $facility->capacity ?? 'Non définie' }} personnes</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Data -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Recent Water Tests -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Derniers Tests d'Eau</h3>
                @if($facility->waterTests->count() > 0)
                    <div class="space-y-3">
                        @foreach($facility->waterTests->take(5) as $test)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $test->test_date->format('d/m/Y H:i') }}</p>
                                    <p class="text-xs text-slate-500">pH: {{ $test->ph }} | Cl: {{ $test->chlorine_free }} ppm</p>
                                </div>
                                <a href="{{ route('pool.water-tests.show', $test) }}" class="text-sm text-blue-600 hover:text-blue-800">Voir</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-400 text-sm">Aucun test enregistré.</p>
                @endif
            </div>

            <!-- Recent Incidents -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Incidents Récents</h3>
                @if($facility->incidents->count() > 0)
                    <div class="space-y-3">
                        @foreach($facility->incidents->take(5) as $incident)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-100">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $incident->title }}</p>
                                    <p class="text-xs text-slate-500">{{ $incident->created_at->format('d/m/Y') }} - {{ ucfirst($incident->status) }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded {{ $incident->severity == 'critical' ? 'bg-red-100 text-red-700' : 'bg-slate-200 text-slate-700' }}">
                                    {{ ucfirst($incident->severity) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-400 text-sm">Aucun incident signalé.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
