@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8">
        <a href="{{ route('pool.maintenance.index') }}" class="text-sm text-slate-500 hover:text-slate-700 mb-2 inline-block">&larr; Retour au planning</a>
        <h1 class="text-3xl font-bold text-slate-800">Planifier une Maintenance</h1>
        <p class="text-slate-500">Créer une nouvelle tâche de maintenance</p>
    </div>

    <div class="max-w-2xl mx-auto">
        <form action="{{ route('pool.maintenance.store') }}" method="POST" class="bg-white rounded-xl shadow-sm p-8">
            @csrf

            <div class="space-y-6">
                <!-- Equipment -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Équipement Concerné</label>
                    <select name="equipment_id" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        @foreach($equipment as $item)
                            <option value="{{ $item->equipment_id }}" {{ request('equipment_id') == $item->equipment_id ? 'selected' : '' }}>
                                {{ $item->name }} ({{ $item->serial_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Task Type -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Type de Tâche</label>
                    <select name="task_type" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="preventive">Maintenance Préventive</option>
                        <option value="corrective">Maintenance Corrective (Réparation)</option>
                        <option value="inspection">Inspection / Contrôle</option>
                        <option value="cleaning">Nettoyage Approfondi</option>
                    </select>
                </div>

                <!-- Scheduled Date -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Date Prévue</label>
                    <input type="date" name="scheduled_date" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Technician (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Assigner à (Optionnel)</label>
                    <select name="technician_id" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Non assigné --</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->staff_id }}">{{ $tech->first_name }} {{ $tech->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Description de la tâche</label>
                    <textarea name="description" rows="4" class="w-full rounded-lg border-slate-200 focus:border-blue-500 focus:ring-blue-500" placeholder="Détails des opérations à effectuer..."></textarea>
                </div>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="{{ route('pool.maintenance.index') }}" class="px-6 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Annuler</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">Planifier</button>
            </div>
        </form>
    </div>
</div>
@endsection
