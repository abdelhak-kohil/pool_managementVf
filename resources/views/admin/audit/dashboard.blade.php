@extends('layouts.app')
@section('title', 'Audit Logs')

@section('content')
<div x-data="{ showModal: false, log: {} }" class="space-y-6">

  <!-- Header -->
  <div class="flex justify-between items-center">
    <h1 class="text-2xl font-semibold text-gray-800">📊 Tableau de bord des Logs d'Audit</h1>
    <span class="text-sm text-gray-500">Administrateurs uniquement</span>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow p-4 border border-gray-100">
      <h3 class="text-gray-500 text-sm mb-1">Total des logs</h3>
      <p class="text-2xl font-semibold text-blue-700">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 border border-gray-100">
      <h3 class="text-gray-500 text-sm mb-1">Ce mois-ci</h3>
      <p class="text-2xl font-semibold text-green-700">{{ number_format($stats['this_month']) }}</p>
    </div>
    <div class="bg-white rounded-xl shadow p-4 border border-gray-100">
      <h3 class="text-gray-500 text-sm mb-1">Tables les plus modifiées</h3>
      <ul class="text-sm text-gray-700">
        @foreach ($stats['tables'] as $t)
          <li class="flex justify-between"><span>{{ $t->table_name }}</span> <span class="font-semibold">{{ $t->count }}</span></li>
        @endforeach
      </ul>
    </div>
  </div>

  <!-- Filters -->
  <form method="GET" class="bg-white rounded-xl shadow p-4 border border-gray-100 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <input type="text" name="table" value="{{ $filters['table'] }}" placeholder="Nom de table" class="border rounded-lg p-2">
      <select name="action" class="border rounded-lg p-2">
        <option value="">Action</option>
        <option value="INSERT" {{ $filters['action']=='INSERT' ? 'selected' : '' }}>Insertion</option>
        <option value="UPDATE" {{ $filters['action']=='UPDATE' ? 'selected' : '' }}>Mise à jour</option>
        <option value="DELETE" {{ $filters['action']=='DELETE' ? 'selected' : '' }}>Suppression</option>
      </select>
      <input type="text" name="user" value="{{ $filters['user'] }}" placeholder="Utilisateur" class="border rounded-lg p-2">
      <input type="date" name="start_date" value="{{ $filters['start_date'] }}" class="border rounded-lg p-2">
      <input type="date" name="end_date" value="{{ $filters['end_date'] }}" class="border rounded-lg p-2">
    </div>
    <div class="flex justify-end gap-3">
      <a href="{{ route('audit.dashboard') }}" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Réinitialiser</a>
      <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Filtrer</button>
    </div>
  </form>

  <!-- Logs Table -->
  <div class="bg-white rounded-xl shadow overflow-x-auto border border-gray-100">
    <table class="min-w-full text-sm text-gray-800">
      <thead class="bg-gray-50 border-b">
        <tr>
          <th class="py-2 px-4">Date</th>
          <th class="py-2 px-4">Table</th>
          <th class="py-2 px-4">Action</th>
          <th class="py-2 px-4">Utilisateur</th>
          <th class="py-2 px-4">Détails</th>
        </tr>
      </thead>
      <tbody class="divide-y">
        @forelse ($logs as $log)
          <tr class="hover:bg-blue-50">
            <td class="py-2 px-4">{{ \Carbon\Carbon::parse($log->change_timestamp)->format('d/m/Y H:i') }}</td>
            <td class="py-2 px-4">{{ $log->table_name }}</td>
            <td class="py-2 px-4 font-semibold 
              {{ $log->action == 'INSERT' ? 'text-green-600' : ($log->action == 'UPDATE' ? 'text-blue-600' : 'text-red-600') }}">
              {{ ucfirst(strtolower($log->action)) }}
            </td>
            <td class="py-2 px-4">{{ $log->user_first_name }} {{ $log->user_last_name }}</td>
            <td class="py-2 px-4 text-right">
              <button class="text-blue-600 hover:text-blue-800 font-medium"
                @click.prevent="fetch(`/admin/audit/show/{{ $log->log_id }}`).then(res => res.json()).then(d => { if(d.success){ log=d.data; showModal=true; } })">
                👁 Voir
              </button>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-center text-gray-500 py-4">Aucun log trouvé</td></tr>
        @endforelse
      </tbody>
    </table>
    <div class="p-3">{{ $logs->links() }}</div>
  </div>

  <!-- Modal -->
  <div x-show="showModal" x-transition class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50">
    <div class="bg-white rounded-xl shadow-lg w-[90%] md:w-[700px] p-6 relative">
      <button class="absolute top-3 right-3 text-gray-600 hover:text-gray-800" @click="showModal=false">✕</button>
      <h3 class="text-xl font-semibold mb-4 text-gray-800">Détails du Log</h3>
      <p class="text-sm text-gray-600 mb-2"><strong>Table :</strong> <span x-text="log.table_name"></span></p>
      <p class="text-sm text-gray-600 mb-2"><strong>Action :</strong> <span x-text="log.action"></span></p>
      <p class="text-sm text-gray-600 mb-2"><strong>Effectué par :</strong> <span x-text="(log.staff_first_name ?? '') + ' ' + (log.staff_last_name ?? '')"></span></p>
      <p class="text-sm text-gray-600 mb-4"><strong>Date :</strong> <span x-text="log.change_timestamp"></span></p>

      <div class="grid grid-cols-2 gap-4 text-xs text-gray-700">
        <div>
          <h4 class="font-semibold text-blue-700 mb-1">Anciennes données</h4>
          <pre class="bg-gray-50 border rounded p-2 overflow-auto max-h-60" x-text="JSON.stringify(JSON.parse(log.old_data_jsonb || '{}'), null, 2)"></pre>
        </div>
        <div>
          <h4 class="font-semibold text-green-700 mb-1">Nouvelles données</h4>
          <pre class="bg-gray-50 border rounded p-2 overflow-auto max-h-60" x-text="JSON.stringify(JSON.parse(log.new_data_jsonb || '{}'), null, 2)"></pre>
        </div>
      </div>

      <div class="mt-4 text-right">
        <button class="px-4 py-2 border rounded-lg hover:bg-gray-100" @click="showModal=false">Fermer</button>
      </div>
    </div>
  </div>
</div>
@endsection
