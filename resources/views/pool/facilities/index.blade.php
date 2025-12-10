@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Bassins & Installations</h1>
            <p class="text-slate-500">Gestion des piscines et spas</p>
        </div>
        <a href="{{ route('pool.facilities.create') }}" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nouveau Bassin
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 rounded-lg bg-red-100 p-4 text-red-700 border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($facilities as $facility)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-100 hover:shadow-md transition group">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $facility->status === 'open' ? 'bg-green-100 text-green-800' : ($facility->status === 'maintenance' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($facility->status) }}
                        </span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $facility->name }}</h3>
                    <p class="text-sm text-slate-500 mb-4">{{ ucfirst(str_replace('_', ' ', $facility->type)) }}</p>

                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between">
                            <span>Volume:</span>
                            <span class="font-medium">{{ number_format($facility->volume_liters, 0, ',', ' ') }} L</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Temp. Cible:</span>
                            <span class="font-medium">{{ $facility->min_temperature }} - {{ $facility->max_temperature }}°C</span>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex justify-end items-center gap-3">
                    <a href="{{ route('pool.facilities.edit', $facility) }}" class="text-sm font-medium text-slate-600 hover:text-blue-600">Modifier</a>
                    <a href="{{ route('pool.facilities.show', $facility) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Détails &rarr;</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
