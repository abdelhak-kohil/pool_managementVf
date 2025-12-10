@extends('layouts.app')
@section('title', 'Pointage Personnel')

@section('content')
<div class="min-h-[80vh] flex flex-col items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8" x-data="pointageSystem()">
    
    <!-- Clock & Header -->
    <div class="text-center mb-10 space-y-2">
        <h2 class="text-3xl font-extrabold text-blue-900 tracking-tight">Kiosque de Pointage</h2>
        <p class="text-lg text-blue-600 font-medium" x-text="currentTime"></p>
        <p class="text-gray-500 text-sm">Veuillez scanner votre badge ou entrer votre ID</p>
    </div>

    <!-- Interface Card -->
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
        <div class="p-8">
            <!-- Status Message area -->
            <div x-show="message" x-transition 
                 :class="success ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                 class="mb-6 p-4 rounded-lg border flex items-start gap-3">
                <div x-show="success">
                    <svg class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div x-show="!success">
                    <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div class="flex-1">
                    <p class="font-bold" x-text="success ? 'Succès' : 'Erreur'"></p>
                    <p class="text-sm" x-text="message"></p>
                </div>
            </div>

            <!-- Input Form -->
            <form @submit.prevent="submitPointage" class="space-y-6">
                
                <!-- Badge visual -->
                <div class="flex justify-center mb-6">
                    <div class="h-24 w-24 bg-blue-50 rounded-full flex items-center justify-center border-4 border-blue-100 animate-pulse">
                         <svg class="h-10 w-10 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <div>
                    <label for="identifier" class="sr-only">Badge ID ou Identifiant</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <input id="identifier" x-model="identifier" x-ref="inputField" type="password" required 
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-lg py-3 text-center tracking-widest" 
                               placeholder="Scannez votre badge..." autofocus autocomplete="off">
                    </div>
                </div>
                
                <!-- Optional Password for manual entry -->
                <div x-show="showPassword" x-transition class="mt-4">
                     <label for="password" class="sr-only">Mot de passe / PIN</label>
                      <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input id="password" x-model="password" type="password" 
                               class="focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-lg py-3 text-center" 
                               placeholder="Code PIN / Mot de passe">
                    </div>
                </div>

                <div class="flex justify-between items-center text-sm">
                    <button type="button" @click="toggleMode" class="text-blue-600 hover:text-blue-500 font-medium focus:outline-none">
                        <span x-text="showPassword ? 'Scanner Badge' : 'Entrée Manuelle (PIN)'"></span>
                    </button>
                </div>

                <button type="submit" 
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5"
                        :disabled="loading">
                    <span x-show="!loading">Valider</span>
                    <span x-show="loading" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Traitement...
                    </span>
                </button>
            </form>
        </div>
        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
             <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Système de gestion RH v1.0</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('pointageSystem', () => ({
            currentTime: '',
            identifier: '',
            password: '',
            showPassword: false,
            loading: false,
            message: '',
            success: false,
            timer: null,

            init() {
                this.updateTime();
                setInterval(() => this.updateTime(), 1000);
                this.$refs.inputField.focus();
                
                // Re-focus input automatically if idle (optional, good for kiosk)
                document.addEventListener('click', () => {
                     if (!this.showPassword) this.$refs.inputField.focus();
                });
            },

            updateTime() {
                const now = new Date();
                this.currentTime = now.toLocaleTimeString('fr-FR', { 
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' 
                });
            },

            toggleMode() {
                this.showPassword = !this.showPassword;
                this.identifier = '';
                this.password = '';
                this.message = '';
                setTimeout(() => {
                    const el = this.showPassword ? document.getElementById('identifier') : this.$refs.inputField;
                    el.focus();
                }, 100);
            },

            async submitPointage() {
                if (!this.identifier) return;
                
                this.loading = true;
                this.message = '';

                try {
                    const response = await fetch('{{ route("staff.hr.pointage.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            identifier: this.identifier,
                            password: this.password
                        })
                    });

                    const data = await response.json();

                    this.success = response.ok; // or data.success
                    this.message = data.message || (this.success ? 'Opération réussie.' : 'Erreur inconnue.');
                    
                    if (this.success) {
                        // Clear inputs
                        this.identifier = '';
                        this.password = '';
                        if (this.showPassword) this.toggleMode(); // Reset to badge mode
                    }

                } catch (error) {
                    this.success = false;
                    this.message = "Erreur de communication avec le serveur.";
                    console.error(error);
                } finally {
                    this.loading = false;
                    // Auto clear message after few seconds
                    setTimeout(() => {
                        this.message = '';
                        if (!this.showPassword) this.$refs.inputField.focus();
                    }, 5000);
                }
            }
        }));
    });
</script>
@endpush
@endsection
