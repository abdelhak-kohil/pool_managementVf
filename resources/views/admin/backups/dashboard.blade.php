@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Tableau de bord des sauvegardes</h1>
        
        <!-- Manual Backup Trigger -->
        <form action="{{ route('admin.backups.store') }}" method="POST" class="flex gap-2">
            @csrf
            <select name="backup_type" class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="full">Sauvegarde complète</option>
                <option value="schema">Schéma uniquement</option>
                <option value="data">Données uniquement</option>
            </select>
            <select name="storage_location" class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                <option value="local">Stockage local</option>
                <option value="network">Stockage réseau</option>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                </svg>
                Démarrer la sauvegarde
            </button>
        </form>
    </div>

    <!-- Alerts -->
    @if($lastBackup && $lastBackup->created_at->diffInDays(now()) >= 7)
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p class="font-bold">Attention</p>
            <p>Aucune sauvegarde réussie n'a été créée au cours des 7 derniers jours. Veuillez lancer une sauvegarde immédiatement.</p>
        </div>
    @endif

    @if($settings && $settings->retention_days < 7)
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
            <p class="font-bold">Alerte de configuration</p>
            <p>La période de rétention est inférieure à 7 jours ({{ $settings->retention_days }} jours). Cela peut être risqué.</p>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Total des sauvegardes</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalBackups }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Dernière sauvegarde réussie</p>
                    <p class="text-lg font-bold text-gray-800">
                        {{ $lastBackup ? $lastBackup->created_at->diffForHumans() : 'Jamais' }}
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 rounded-full {{ $settings && $settings->automatic_enabled ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600' }}">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="ml-4">
                    <p class="text-gray-500 text-sm">Sauvegardes automatiques</p>
                    @if($settings && $settings->automatic_enabled)
                        <p class="text-lg font-bold text-green-600">Activé</p>
                        <p class="text-xs text-gray-500">
                            {{ ucfirst($settings->frequency) }} à {{ $settings->scheduled_time }}
                        </p>
                    @else
                        <p class="text-lg font-bold text-gray-400">Désactivé</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Backup History -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Historique des sauvegardes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fichier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taille</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($jobs as $job)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $job->file_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($job->backup_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->file_size ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($job->status === 'success')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Succès</span>
                                    @elseif($job->status === 'running')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">En cours</span>
                                    @elseif($job->status === 'failed')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800" title="{{ $job->error_message }}">Échec</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">En attente</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $job->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($job->status === 'success')
                                        <a href="{{ route('admin.backups.download', $job->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Télécharger</a>
                                    @endif
                                    <form action="{{ route('admin.backups.destroy', $job->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Supprimer cette sauvegarde ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Aucune sauvegarde trouvée.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $jobs->links() }}
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white rounded-lg shadow p-6 h-fit">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Paramètres</h3>
            <form action="{{ route('admin.backups.settings.update') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input type="checkbox" name="automatic_enabled" id="automatic_enabled" value="1" {{ $settings && $settings->automatic_enabled ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="automatic_enabled" class="ml-2 block text-sm text-gray-900">Activer les sauvegardes automatiques</label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fréquence</label>
                        <select name="frequency" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="daily" {{ $settings && $settings->frequency == 'daily' ? 'selected' : '' }}>Quotidien</option>
                            <option value="weekly" {{ $settings && $settings->frequency == 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                            <option value="monthly" {{ $settings && $settings->frequency == 'monthly' ? 'selected' : '' }}>Mensuel</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Heure programmée</label>
                        <input type="time" name="scheduled_time" value="{{ $settings ? $settings->scheduled_time : '02:00' }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Rétention (Jours)</label>
                        <input type="number" name="retention_days" value="{{ $settings ? $settings->retention_days : 30 }}" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Préférence de stockage</label>
                        <select name="storage_preference" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="local" {{ $settings && $settings->storage_preference == 'local' ? 'selected' : '' }}>Local</option>
                            <option value="network" {{ $settings && $settings->storage_preference == 'network' ? 'selected' : '' }}>Réseau</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Chemin réseau (Optionnel)</label>
                        <input type="text" name="network_path" id="network_path" value="{{ $settings ? $settings->network_path : '' }}" placeholder="\\SERVER\Backups" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <!-- Test Connection Button -->
                    <div class="pt-2">
                        <button type="button" id="testConnectionBtn" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                            Tester la connexion réseau
                        </button>
                        <div id="testConnectionResult" class="mt-2 hidden"></div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Enregistrer les paramètres
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(request('backup_success'))
<div id="successOverlay" class="fixed inset-0 z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeSuccessOverlay()"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-sm p-6">
                <div>
                    <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-16 w-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <div class="mt-5 text-center">
                        <h3 class="text-2xl font-semibold leading-6 text-gray-900" id="modal-title">Succès !</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">Sauvegarde démarrée avec succès.</p>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <button type="button" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600" onclick="closeSuccessOverlay()">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.delete('backup_success');
            window.history.replaceState({path: url.href}, '', url.href);
        }
    });

    function closeSuccessOverlay() {
        document.getElementById('successOverlay').remove();
    }
</script>
@endif

<!-- Loading Overlay -->
<div id="backupLoadingOverlay" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop with blur -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity opacity-0" id="overlayBackdrop"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="overlayPanel">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-blue-600 animate-spin-slow" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">Sauvegarde en cours</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Nous sauvegardons votre base de données en toute sécurité. Veuillez ne pas fermer cette fenêtre.</p>
                            </div>
                            
                            <!-- Progress Bar Container -->
                            <div class="mt-6 relative">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-blue-600 bg-blue-200">
                                            Traitement
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold inline-block text-blue-600">
                                            Veuillez patienter...
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-blue-100">
                                    <div class="animate-progress-indeterminate shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-blue-500 to-indigo-600"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .animate-spin-slow {
        animation: spin 3s linear infinite;
    }
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    @keyframes progress-indeterminate {
        0% { width: 30%; margin-left: -30%; }
        50% { width: 60%; margin-left: 20%; }
        100% { width: 30%; margin-left: 100%; }
    }
    .animate-progress-indeterminate {
        animation: progress-indeterminate 2s infinite ease-in-out;
        width: 100%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action="{{ route('admin.backups.store') }}"]');
        const overlay = document.getElementById('backupLoadingOverlay');
        const backdrop = document.getElementById('overlayBackdrop');
        const panel = document.getElementById('overlayPanel');
        
        if (form && overlay) {
            form.addEventListener('submit', function(e) {
                // Show overlay
                overlay.classList.remove('hidden');
                
                // Animate in
                setTimeout(() => {
                    backdrop.classList.remove('opacity-0');
                    panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                    panel.classList.add('opacity-100', 'translate-y-0', 'sm:scale-100');
                }, 10);
            });
        }

        // Test Connection Handler
        const testBtn = document.getElementById('testConnectionBtn');
        const resultDiv = document.getElementById('testConnectionResult');
        
        if (testBtn) {
            testBtn.addEventListener('click', async function() {
                const networkPath = document.getElementById('network_path').value;
                
                if (!networkPath) {
                    resultDiv.innerHTML = '<p class="text-sm text-yellow-600 p-2 bg-yellow-50 rounded">Veuillez entrer un chemin réseau.</p>';
                    resultDiv.classList.remove('hidden');
                    return;
                }
                
                testBtn.disabled = true;
                testBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Test en cours...';
                
                try {
                    const response = await fetch("{{ route('admin.backups.settings.test-connection') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ network_path: networkPath })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        resultDiv.innerHTML = '<p class="text-sm text-green-600 p-2 bg-green-50 rounded flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> ' + data.message + '</p>';
                    } else {
                        resultDiv.innerHTML = '<p class="text-sm text-red-600 p-2 bg-red-50 rounded flex items-center"><svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> ' + data.message + '</p>';
                    }
                } catch (error) {
                    resultDiv.innerHTML = '<p class="text-sm text-red-600 p-2 bg-red-50 rounded">Erreur de connexion au serveur.</p>';
                }
                
                resultDiv.classList.remove('hidden');
                testBtn.disabled = false;
                testBtn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg> Tester la connexion réseau';
            });
        }
    });
</script>
</div>
@endsection
