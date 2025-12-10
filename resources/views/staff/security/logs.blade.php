@extends('layouts.app')
@section('title', 'Historique des Accès & Sécurité')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-3xl font-extrabold text-gray-900">
                    🛡️ Journaux d'Accès
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Historique complet des ouvertures de portes et interventions.
                </p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('staff.hr.security.simulator') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    📂 Simulateur
                </a>
                <a href="{{ route('staff.hr.dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Retour Dashboard
                </a>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date & Heure
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Personnel / Badge
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Lieu
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Action
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($log->access_time)->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($log->first_name)
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $log->first_name }} {{ $log->last_name }}
                                    </div>
                                @else
                                    <div class="text-sm text-gray-500">
                                        Badge: <span class="font-mono">{{ $log->badge_uid }}</span>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $log->location ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @switch($log->action_type)
                                @case('entry') 入 Entrée @break
                                @case('exit') 出 Sortie @break
                                @case('door_open') 🚪 Ouvrir Porte @break
                                @case('maintenance_start') 🔧 Maintenance @break
                                @default {{ $log->action_type }}
                            @endswitch
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->access_decision === 'granted')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Autorisé
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" title="{{ $log->denial_reason }}">
                                    Refusé
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Aucun historique d'accès disponible.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
