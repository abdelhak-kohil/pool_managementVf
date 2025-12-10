@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Équipements</h1>
            <p class="text-slate-500">Gestion du parc matériel et technique</p>
        </div>
        <a href="{{ route('pool.equipment.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Ajouter
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($equipment as $item)
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-100 hover:shadow-md transition group">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="p-3 rounded-lg {{ $item->status === 'operational' ? 'bg-blue-50 text-blue-600' : ($item->status === 'warning' ? 'bg-orange-50 text-orange-600' : 'bg-red-50 text-red-600') }}">
                            @if($item->type === 'pump')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            @elseif($item->type === 'filter')
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            @endif
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $item->status === 'operational' ? 'bg-green-100 text-green-800' : ($item->status === 'warning' ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </div>
                    
                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $item->name }}</h3>
                    <p class="text-sm text-slate-500 mb-4">{{ $item->serial_number }}</p>

                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex justify-between">
                            <span>Installation:</span>
                            <span class="font-medium">{{ $item->install_date ? $item->install_date->format('d/m/Y') : '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Prochaine Maint.:</span>
                            <span class="font-medium {{ $item->next_due_date && $item->next_due_date->isPast() ? 'text-red-600' : '' }}">
                                {{ $item->next_due_date ? $item->next_due_date->format('d/m/Y') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex justify-between items-center">
                    <a href="{{ route('pool.equipment.show', $item) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">Gérer &rarr;</a>
                    <!-- Mockup QR Code Action -->
                    <button class="text-slate-400 hover:text-slate-600" title="Scanner QR Code">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
