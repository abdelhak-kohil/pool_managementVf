@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.equipment.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour aux équipements</a>
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">{{ $equipment->name }}</h1>
                <p class="text-slate-500">S/N: {{ $equipment->serial_number }}</p>
            </div>
            <div class="flex gap-3 items-start">
                <a href="{{ route('pool.equipment.edit', $equipment) }}" class="px-3 py-2 bg-white border border-slate-200 text-slate-600 text-sm rounded-lg hover:bg-slate-50 transition">Modifier</a>
                <form action="{{ route('pool.equipment.update-status', $equipment) }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <select name="status" class="text-sm rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="operational" {{ $equipment->status == 'operational' ? 'selected' : '' }}>Opérationnel</option>
                        <option value="warning" {{ $equipment->status == 'warning' ? 'selected' : '' }}>Attention</option>
                        <option value="failure" {{ $equipment->status == 'failure' ? 'selected' : '' }}>En Panne</option>
                    </select>
                    <button type="submit" class="px-3 py-2 bg-slate-800 text-white text-sm rounded-lg hover:bg-slate-700 transition">Mettre à jour</button>
                </form>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Details Card -->
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Informations</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Type</p>
                        <p class="font-medium text-slate-700">{{ ucfirst($equipment->type) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Emplacement</p>
                        <p class="font-medium text-slate-700">{{ $equipment->location ?? 'Non spécifié' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Date d'installation</p>
                        <p class="font-medium text-slate-700">{{ $equipment->install_date ? $equipment->install_date->format('d/m/Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase">Prochaine Maintenance</p>
                        <p class="font-medium {{ $equipment->next_due_date && $equipment->next_due_date->isPast() ? 'text-red-600' : 'text-slate-700' }}">
                            {{ $equipment->next_due_date ? $equipment->next_due_date->format('d/m/Y') : '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- QR Code Placeholder -->
            <div class="bg-white rounded-xl shadow-sm p-6 text-center">
                <div class="bg-slate-100 h-32 w-32 mx-auto rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4h2v-4zM6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <p class="text-sm text-slate-500">QR Code pour accès rapide</p>
            </div>
        </div>

        <!-- History & Maintenance -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Incidents -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Incidents Récents</h3>
                    <a href="{{ route('pool.incidents.create', ['equipment_id' => $equipment->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">Signaler +</a>
                </div>
                @if($equipment->incidents->count() > 0)
                    <div class="space-y-3">
                        @foreach($equipment->incidents->take(5) as $incident)
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

            <!-- Maintenance Log -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-slate-800">Historique Maintenance</h3>
                    <a href="{{ route('pool.maintenance.create', ['equipment_id' => $equipment->id]) }}" class="text-sm text-blue-600 hover:text-blue-800">Planifier +</a>
                </div>
                @if($equipment->maintenanceLogs->count() > 0)
                    <div class="space-y-4">
                        @foreach($equipment->maintenanceLogs->take(5) as $log)
                            <div class="relative pl-6 border-l-2 {{ $log->status == 'completed' ? 'border-green-300' : 'border-slate-200' }}">
                                <div class="absolute -left-[5px] top-1 h-2.5 w-2.5 rounded-full {{ $log->status == 'completed' ? 'bg-green-500' : 'bg-slate-300' }}"></div>
                                <p class="text-sm font-medium text-slate-800">{{ ucfirst($log->task_type) }}</p>
                                <p class="text-xs text-slate-500 mb-1">Prévu le {{ $log->scheduled_date->format('d/m/Y') }}</p>
                                @if($log->description)
                                    <p class="text-sm text-slate-600 bg-slate-50 p-2 rounded">{{ $log->description }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-slate-400 text-sm">Aucune maintenance enregistrée.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
