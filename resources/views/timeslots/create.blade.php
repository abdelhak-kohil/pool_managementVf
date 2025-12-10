@extends('layouts.app')
@section('title','Ajouter un créneau')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-gray-800">➕ Ajouter un créneau</h2>
        <a href="{{ route('timeslots.index') }}" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg transition">
            ← Retour
        </a>
    </div>

    <!-- Form Card -->
    <div class="bg-white shadow rounded-xl border border-gray-100 p-8">
        <form action="{{ route('timeslots.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Jour</label>
                    <select name="weekday_id" 
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                            required>
                        @foreach($weekdays as $d)
                            <option value="{{ $d->weekday_id }}">{{ ucfirst($d->day_name) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Début (HH:MM)</label>
                    <input name="start_time" type="time" 
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                           required>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Fin (HH:MM)</label>
                    <input name="end_time" type="time" 
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200" 
                           required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Activité</label>
                    <select name="activity_id" 
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
                        <option value="">— Aucun —</option>
                        @foreach($activities as $a)
                            <option value="{{ $a->activity_id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Groupe assigné (optionnel)</label>
                    <input name="assigned_group" type="text" 
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Capacité Max</label>
                    <input name="capacity" type="number" min="1"
                           class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Coach Principal</label>
                    <select name="coach_id" 
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
                        <option value="">— Aucun —</option>
                        @foreach($coaches as $c)
                            <option value="{{ $c->staff_id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-medium mb-2">Assistant Coach</label>
                    <select name="assistant_coach_id" 
                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200">
                        <option value="">— Aucun —</option>
                        @foreach($coaches as $c)
                            <option value="{{ $c->staff_id }}">{{ $c->first_name }} {{ $c->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-medium mb-2">Notes</label>
                <textarea name="notes" rows="3" 
                          class="w-full bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 shadow-sm transition duration-200"></textarea>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100">
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-md">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
