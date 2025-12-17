@extends('layouts.app')

@section('title', 'Gestion de Licence')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Gestion de Licence (Multi-Module)</h1>
            <p class="text-gray-600">État et configuration des licences par module.</p>
            <p class="text-sm text-gray-500 mt-2">Client Global: <span class="font-medium text-gray-900">{{ $client }}</span></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($modules as $moduleName => $info)
            @php
                $isValid = $info['valid'];
                $isEnabled = $info['enabled'];
            @endphp
            <div class="bg-white rounded-xl shadow-sm border {{ $isValid ? 'border-green-200' : 'border-red-200' }} p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-lg {{ $isValid ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold capitalize text-gray-800">{{ $moduleName }}</h2>
                            <span class="text-xs {{ $isValid ? 'text-green-600' : 'text-red-500' }}">
                                {{ $isValid ? 'LICENCE ACTIVE' : 'LICENCE INACTIVE' }}
                            </span>
                            @if($isValid && isset($info['expiry_date']))
                                <p class="text-xs text-gray-500 mt-1">
                                    Expire le: <b>{{ $info['expiry_date'] }}</b> 
                                    ({{ $info['days_remaining'] }} jours restants)
                                </p>
                            @endif
                        </div>
                    </div>
                     <form action="{{ route('admin.license.toggle') }}" method="POST">
                        @csrf
                        <input type="hidden" name="module" value="{{ $moduleName }}">
                        @if($isValid)
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 underline" onclick="return confirm('Désactiver ce module ?')">
                                Désactiver
                            </button>
                        @else
                            <button type="submit" class="text-xs text-green-600 hover:text-green-800 underline">
                                Réactiver
                            </button>
                        @endif
                    </form>
                </div>

                <div class="mb-4 text-sm text-gray-600">
                    <p>Accès Module: 
                        @if($isEnabled)
                            <span class="text-green-600 font-bold">AUTORISÉ</span>
                        @else
                            <span class="text-red-600 font-bold">REFUSÉ</span>
                        @endif
                    </p>
                </div>

                <!-- Update Form -->
                <form action="{{ route('admin.license.update') }}" method="POST" class="mt-4 pt-4 border-t border-gray-100">
                    @csrf
                    <div class="mb-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Nouvelle Clé ({{ ucfirst($moduleName) }})</label>
                        <textarea name="license_key" rows="2" class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono text-xs" placeholder="Collez la clé ici..."></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-medium rounded transition-colors">
                            Appliquer Licence
                        </button>
                    </div>
                </form>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
