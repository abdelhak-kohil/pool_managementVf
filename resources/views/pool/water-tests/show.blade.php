@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.water-tests.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux relevés</a>
        <h1 class="text-3xl font-bold text-slate-800">Détail du Relevé</h1>
        <p class="text-slate-500">Effectué le {{ $waterTest->test_date->format('d/m/Y à H:i') }} par {{ $waterTest->technician->name ?? 'Inconnu' }}</p>
    </div>

    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-8">
            <div class="flex items-center justify-between mb-8 pb-8 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-800">{{ $waterTest->pool->name ?? 'Bassin Inconnu' }}</h2>
                    <span class="inline-flex items-center mt-2 rounded-full px-2.5 py-0.5 text-xs font-medium {{ ($waterTest->ph < 7.2 || $waterTest->ph > 7.8) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        Statut: {{ ($waterTest->ph < 7.2 || $waterTest->ph > 7.8) ? 'Attention Recommandée' : 'Normal' }}
                    </span>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-500">ID Relevé</p>
                    <p class="font-mono text-slate-800">#{{ $waterTest->test_id }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-8 mb-8">
                <div>
                    <p class="text-sm text-slate-500 mb-1">pH</p>
                    <p class="text-2xl font-bold {{ ($waterTest->ph < 7.2 || $waterTest->ph > 7.8) ? 'text-red-600' : 'text-slate-800' }}">{{ $waterTest->ph ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Chlore Libre</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $waterTest->chlorine_free ?? '-' }} <span class="text-sm font-normal text-slate-400">ppm</span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Température</p>
                    <p class="text-2xl font-bold text-slate-800">{{ $waterTest->temperature ?? '-' }} <span class="text-sm font-normal text-slate-400">°C</span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">Chlore Total</p>
                    <p class="text-xl font-semibold text-slate-700">{{ $waterTest->chlorine_total ?? '-' }} <span class="text-sm font-normal text-slate-400">ppm</span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">TAC (Alcalinité)</p>
                    <p class="text-xl font-semibold text-slate-700">{{ $waterTest->alkalinity ?? '-' }} <span class="text-sm font-normal text-slate-400">mg/L</span></p>
                </div>
                <div>
                    <p class="text-sm text-slate-500 mb-1">TH (Dureté)</p>
                    <p class="text-xl font-semibold text-slate-700">{{ $waterTest->hardness ?? '-' }} <span class="text-sm font-normal text-slate-400">mg/L</span></p>
                </div>
            </div>

            @if($waterTest->comments)
                <div class="bg-slate-50 rounded-lg p-6 border border-slate-100">
                    <h3 class="text-sm font-semibold text-slate-800 mb-2">Commentaires du technicien</h3>
                    <p class="text-slate-600">{{ $waterTest->comments }}</p>
                </div>
            @endif
        </div>
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex justify-end">
             <!-- Future: Edit/Delete buttons if needed -->
        </div>
    </div>
</div>
@endsection
