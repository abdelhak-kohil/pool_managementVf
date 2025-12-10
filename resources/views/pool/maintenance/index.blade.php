@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 p-6">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800">Maintenance</h1>
            <p class="text-slate-500">Suivi des tâches et interventions</p>
        </div>
        <a href="{{ route('pool.maintenance.create') }}" class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-white shadow hover:bg-blue-700 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Planifier Tâche
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
        
        <!-- Scheduled Column -->
        <div class="bg-slate-100 rounded-xl p-4 flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-700">À Faire</h3>
                <span class="bg-slate-200 text-slate-600 px-2 py-1 rounded-full text-xs font-bold">{{ $kanban['scheduled']->count() }}</span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1">
                @foreach($kanban['scheduled'] as $task)
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 cursor-pointer hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ ucfirst($task->task_type) }}</span>
                            <span class="text-xs text-slate-400">{{ $task->scheduled_date->format('d/m') }}</span>
                        </div>
                        <h4 class="font-medium text-slate-800 mb-1">{{ $task->equipment->name ?? 'Général' }}</h4>
                        <p class="text-sm text-slate-500 line-clamp-2 mb-3">{{ $task->description }}</p>
                        
                        <form action="{{ route('pool.maintenance.update-status', $task) }}" method="POST">
                            @csrf
                            <input type="hidden" name="status" value="in_progress">
                            <button type="submit" class="w-full py-1.5 bg-slate-50 text-slate-600 text-xs font-medium rounded hover:bg-slate-100 border border-slate-200 transition">
                                Commencer &rarr;
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- In Progress Column -->
        <div class="bg-blue-50 rounded-xl p-4 flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-blue-800">En Cours</h3>
                <span class="bg-blue-200 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">{{ $kanban['in_progress']->count() }}</span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1">
                @foreach($kanban['in_progress'] as $task)
                    <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500 cursor-pointer hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">{{ ucfirst($task->task_type) }}</span>
                            <span class="text-xs text-slate-400">{{ $task->scheduled_date->format('d/m') }}</span>
                        </div>
                        <h4 class="font-medium text-slate-800 mb-1">{{ $task->equipment->name ?? 'Général' }}</h4>
                        <p class="text-sm text-slate-500 line-clamp-2 mb-3">{{ $task->description }}</p>
                        
                        <form action="{{ route('pool.maintenance.update-status', $task) }}" method="POST" class="space-y-2">
                            @csrf
                            <input type="hidden" name="status" value="completed">
                            <input type="number" name="working_hours_spent" placeholder="Heures passées" class="w-full text-xs border-slate-200 rounded px-2 py-1" step="0.5">
                            <button type="submit" class="w-full py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition">
                                Terminer ✓
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Completed Column -->
        <div class="bg-green-50 rounded-xl p-4 flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-green-800">Terminé</h3>
                <span class="bg-green-200 text-green-800 px-2 py-1 rounded-full text-xs font-bold">{{ $kanban['completed']->count() }}</span>
            </div>
            <div class="space-y-3 overflow-y-auto flex-1">
                @foreach($kanban['completed']->take(10) as $task)
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-green-100 opacity-75 hover:opacity-100 transition">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">Fait le {{ $task->completed_date ? $task->completed_date->format('d/m') : '-' }}</span>
                        </div>
                        <h4 class="font-medium text-slate-800 mb-1">{{ $task->equipment->name ?? 'Général' }}</h4>
                        <p class="text-sm text-slate-500 line-clamp-1">{{ $task->description }}</p>
                        @if($task->working_hours_spent)
                            <p class="text-xs text-slate-400 mt-2">Durée: {{ $task->working_hours_spent }}h</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
