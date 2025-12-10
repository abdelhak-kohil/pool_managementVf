@extends('layouts.app')
@section('title', 'Simulateur d\'Accès & Sécurité')

@section('content')
<div class="py-12" x-data="accessSimulator()">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="text-center mb-10">
            <h2 class="text-3xl font-extrabold text-gray-900">
                🔐 Simulateur de Contrôle d'Accès
            </h2>
            <p class="mt-4 text-lg text-gray-500">
                Simulez l'utilisation des badges pour l'ouverture de portes ou les interventions.
            </p>
        </div>

        <div class="bg-white shadow-xl rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
            
            <!-- Left Panel: Configuration -->
            <div class="p-8 bg-gray-50 border-r border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 mb-6">📍 Point d'Accès</h3>
                
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lieu (Zone)</label>
                        <select x-model="location" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="Main Entrance">Porte Principale (Public)</option>
                            <option value="Staff Entrance">Entrée Personnel</option>
                            <option value="Technical Room">Local Technique (Maintenance)</option>
                            <option value="Pool Gate">Barrière Bassin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Action Requise</label>
                        <select x-model="actionType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="entry">Entrée Simple</option>
                            <option value="exit">Sortie</option>
                            <option value="door_open">Ouvrir Porte (Sécurité)</option>
                            <option value="maintenance_start">Début Maintenance</option>
                        </select>
                    </div>

                    <div class="pt-4">
                        <label class="block text-sm font-medium text-gray-700">Badge UID (Simulation)</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="text" x-model="badgeUid" @keydown.enter="scan()" class="focus:ring-blue-500 focus:border-blue-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" placeholder="Scanner Badge...">
                            <button @click="scan()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-r-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                Scanner
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Entrez un UID valide (ex: celui d'un employé) ou invalide pour tester.</p>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Feedback -->
            <div class="p-8 flex flex-col justify-center items-center text-center relative">
                
                <!-- Status Indicator -->
                <div class="w-24 h-24 rounded-full flex items-center justify-center mb-6 transition-colors duration-500"
                     :class="statusColor">
                    <svg x-show="status === 'idle'" class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <svg x-show="status === 'granted'" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <svg x-show="status === 'denied'" class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900" x-text="message">En attente...</h3>
                <p class="mt-2 text-sm text-gray-500" x-show="detail" x-text="detail"></p>

                <!-- Loading Overlay -->
                <div x-show="loading" class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center">
                    <svg class="animate-spin h-10 w-10 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="mt-8 flex justify-center space-x-4">
            <a href="{{ route('staff.hr.dashboard') }}" class="text-blue-600 hover:text-blue-500 font-medium">← Retour au Dashboard RH</a>
            <span class="text-gray-300">|</span>
            <a href="{{ route('staff.hr.security.logs') }}" class="text-blue-600 hover:text-blue-500 font-medium">📜 Voir l'Historique</a>
        </div>
    </div>
</div>

<script>
    function accessSimulator() {
        return {
            badgeUid: '',
            location: 'Main Entrance',
            actionType: 'entry',
            status: 'idle', // idle, granted, denied
            message: 'Prêt à scanner',
            detail: '',
            loading: false,

            get statusColor() {
                if (this.status === 'granted') return 'bg-green-500 shadow-lg shadow-green-200';
                if (this.status === 'denied') return 'bg-red-500 shadow-lg shadow-red-200';
                return 'bg-gray-200';
            },

            async scan() {
                if (!this.badgeUid) return;
                
                this.loading = true;
                this.status = 'idle';
                this.message = 'Vérification...';
                this.detail = '';

                try {
                    const response = await fetch('{{ route("staff.hr.security.scan") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            badge_uid: this.badgeUid,
                            location: this.location,
                            action_type: this.actionType
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.status = 'granted';
                        this.message = 'ACCÈS AUTORISÉ';
                        this.detail = data.message;
                    } else {
                        this.status = 'denied';
                        this.message = 'ACCÈS REFUSÉ';
                        this.detail = data.message;
                    }
                } catch (error) {
                    this.status = 'denied';
                    this.message = 'Erreur Système';
                    this.detail = 'Impossible de contacter le serveur.';
                } finally {
                    this.loading = false;
                    // Reset field for next scan check? 
                    // this.badgeUid = ''; 
                }
            }
        }
    }
</script>
@endsection
