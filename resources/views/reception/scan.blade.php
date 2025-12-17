<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Borne d'Accès</title>
    
    <!-- Fonts -->
    <link href="{{ asset('vendor/fonts/dm-sans.css') }}" rel="stylesheet">
    
    <!-- Scripts -->
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://unpkg.com/laravel-echo@1.16.1/dist/echo.iife.js"></script>
    <script src="{{ asset('vendor/tailwindcss/tailwindcss.js') }}"></script>
    @vite(['resources/js/app.js'])

    <style>
        body { font-family: 'DM Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .blob {
            position: absolute;
            filter: blur(80px);
            z-index: 0;
            opacity: 0.6;
            animation: move 20s infinite alternate;
        }
        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(20px, -20px) scale(1.1); }
        }
    </style>
</head>
<body class="bg-slate-50 h-screen w-full overflow-hidden flex flex-col relative" x-data="kioskScanner()" x-init="initScanner()">

    <!-- Animated Background -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="blob bg-blue-300 w-96 h-96 rounded-full top-0 left-0 mix-blend-multiply"></div>
        <div class="blob bg-cyan-300 w-96 h-96 rounded-full bottom-0 right-0 mix-blend-multiply animation-delay-2000"></div>
        <div class="blob bg-indigo-300 w-80 h-80 rounded-full top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 mix-blend-multiply animation-delay-4000"></div>
    </div>

    <!-- Top Bar -->
    <header class="relative z-10 w-full p-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="h-10 w-10 bg-white rounded-xl shadow-md flex items-center justify-center text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-800 tracking-tight">PoolManager<span class="text-blue-600">Pro</span></h1>
        </div>

        <div class="glass-panel px-4 py-2 rounded-full flex items-center gap-3 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="relative flex h-3 w-3">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                </span>
                <span class="text-sm font-medium text-slate-600">Borne Connectée</span>
            </div>
            <div class="h-4 w-px bg-slate-300"></div>
            <span class="text-sm font-medium text-slate-500" x-text="currentTime"></span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 flex flex-col items-center justify-center px-4 transition-all duration-500">
        
        <!-- IDLE STATE -->
        <div x-show="state === 'idle'" 
             x-transition:enter="transition ease-out duration-700" 
             x-transition:enter-start="opacity-0 translate-y-8" 
             x-transition:enter-end="opacity-100 translate-y-0"
             class="text-center space-y-8">
            
            <div class="relative inline-block group">
                <div class="absolute inset-0 bg-blue-500 rounded-full opacity-20 group-hover:opacity-30 transition-opacity duration-500 blur-xl"></div>
                <!-- Animated Pulse Rings -->
                <div class="absolute inset-0 rounded-full border-2 border-blue-500 opacity-20 animate-ping"></div>
                <div class="absolute inset-2 rounded-full border border-blue-400 opacity-40 animate-[ping_3s_linear_infinite]"></div>
                
                <div class="relative w-48 h-48 bg-white/80 backdrop-blur-xl rounded-full shadow-[0_8px_30px_rgb(0,0,0,0.04)] flex items-center justify-center border border-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 7a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2c0 1.1.9 2 2 2h.5a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-.5a2 2 0 0 0-2 2v2a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4v-2a2 2 0 0 0-2-2h-.5a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5H5a2 2 0 0 0 2-2V7z"/>
                        <path d="M10 12a2 2 0 1 0 4 0 2 2 0 0 0-4 0"/>
                    </svg>
                </div>
            </div>

            <div class="space-y-3">
                <h2 class="text-4xl font-bold text-slate-800 tracking-tight">Veuillez scanner votre badge</h2>
                <p class="text-lg text-slate-500 max-w-md mx-auto">Approchez votre carte ou bracelet du lecteur pour enregistrer votre entrée.</p>
            </div>
        </div>

        <!-- SUCCESS STATE -->
        <div x-show="state === 'success'" x-cloak
             x-transition:enter="transition cubic-bezier(0.175, 0.885, 0.32, 1.275) duration-700" 
             x-transition:enter-start="opacity-0 scale-90" 
             x-transition:enter-end="opacity-100 scale-100"
             class="glass-panel w-full max-w-2xl rounded-3xl p-10 shadow-2xl border-t-4 border-emerald-500 text-center relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-emerald-400 to-green-500"></div>

            <div class="flex flex-col items-center gap-6">
                <div class="relative">
                    <img :src="profile.photo || '{{ asset('images/default-avatar.png') }}'" 
                         class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg bg-slate-200"
                         alt="Membre">
                    <div class="absolute -bottom-2 -right-2 bg-emerald-500 text-white rounded-full p-2 border-4 border-white shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <div class="space-y-1">
                    <h2 class="text-3xl font-bold text-slate-800" x-text="profile.name"></h2>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-emerald-100 text-emerald-800" x-text="profile.type"></span>
                    
                    <template x-if="profile.remaining_sessions !== null">
                         <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800" 
                               x-text="'Séances restantes : ' + profile.remaining_sessions"></span>
                    </template>
                </div>

                <div class="w-full bg-slate-50 rounded-xl p-4 border border-slate-100 mt-2">
                    <p class="text-xl font-semibold text-emerald-600" x-text="serverMessage"></p>
                </div>
            </div>
            
            <!-- Confetti Effect (CSS only implementation for simplicity) -->
            <div class="absolute inset-0 pointer-events-none opacity-20 bg-[radial-gradient(circle,_#10b981_2px,transparent_2.5px)] bg-[length:24px_24px]"></div>
        </div>

        <!-- GROUP CHECK-IN FORM -->
        <div x-show="state === 'group_checkin'" x-cloak
             x-transition:enter="transition cubic-bezier(0.175, 0.885, 0.32, 1.275) duration-700" 
             x-transition:enter-start="opacity-0 scale-90" 
             x-transition:enter-end="opacity-100 scale-100"
             class="glass-panel w-full max-w-xl rounded-3xl p-10 shadow-2xl border-t-4 border-blue-500 text-center relative">
            
            <h2 class="text-3xl font-bold text-slate-800 mb-2">Entrée Groupe</h2>
            <h3 class="text-xl text-blue-600 font-semibold mb-6" x-text="profile.name || 'Groupe Inconnu'"></h3>

            <div class="mb-6">
                <label class="block text-slate-600 text-sm font-bold mb-2">Nombre de participants</label>
                <div class="flex items-center justify-center gap-4">
                    <button @click="countInput = Math.max(1, countInput - 1)" 
                            class="w-12 h-12 rounded-full bg-slate-200 text-slate-600 font-bold text-xl hover:bg-slate-300 transition">-</button>
                    <input type="number" x-model.number="countInput" 
                           class="w-24 text-center text-3xl font-bold text-slate-800 border-2 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-0 p-2" 
                           min="1">
                    <button @click="countInput++" 
                            class="w-12 h-12 rounded-full bg-slate-200 text-slate-600 font-bold text-xl hover:bg-slate-300 transition">+</button>
                </div>
            </div>

            <button @click="submitGroupCheck()" 
                    class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-blue-700 transition transform hover:scale-[1.02] active:scale-95">
                Valider l'Entrée
            </button>
        </div>

        <!-- ERROR STATE -->
        <div x-show="state === 'error'" x-cloak
             x-transition:enter="transition cubic-bezier(0.36, 0, 0.66, -0.56) duration-500" 
             x-transition:enter-start="opacity-0 translate-x-8" 
             x-transition:enter-end="opacity-100 translate-x-0"
             class="glass-panel w-full max-w-xl rounded-3xl p-10 shadow-xl border-t-4 border-rose-500 text-center relative">
            
            <div class="absolute top-0 right-0 p-8 opacity-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-40 w-40 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <div class="flex flex-col items-center gap-6 relative z-10">
                
                <!-- Expanded User Info on Error -->
                <template x-if="profile.name">
                    <div class="flex flex-col items-center gap-4 mb-4">
                        <img :src="profile.photo || '{{ asset('images/default-avatar.png') }}'" 
                             class="w-24 h-24 rounded-full object-cover border-4 border-rose-100 shadow-md bg-slate-200"
                             alt="Membre">
                        <h3 class="text-2xl font-bold text-slate-700" x-text="profile.name"></h3>
                    </div>
                </template>

                <div class="w-24 h-24 bg-rose-100 rounded-full flex items-center justify-center mb-2" x-show="!profile.name">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>

                <div class="space-y-2">
                    <h2 class="text-3xl font-bold text-slate-800">Accès Refusé</h2>
                    <p class="text-xl text-rose-600 font-medium" x-text="serverMessage"></p>
                </div>

                <div class="text-sm text-slate-500 mt-4">
                    Veuillez contacter l'accueil.
                </div>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="relative z-10 w-full p-6 text-center">
        <a href="{{ route('reception.index') }}" class="inline-flex items-center gap-2 text-slate-400 hover:text-slate-600 transition-colors text-sm font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
            </svg>
            Retour au tableau de bord
        </a>
    </footer>

    <!-- Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('kioskScanner', () => ({
                state: 'idle', // idle, processing, success, error, group_checkin
                serverMessage: '',
                profile: { name: '', photo: '', type: '' },
                countInput: 1,
                currentBadge: null,
                resetTimer: null,
                currentTime: '',

                initScanner() {
                    // Clock
                    this.updateTime();
                    setInterval(() => this.updateTime(), 1000);

                    // 📡 Real-Time Listener (Hardcoded for Localhost Fix)
                    const initEcho = () => {
                        if (window.Echo && typeof window.Echo.disconnect === 'function') {
                            window.Echo.disconnect();
                        }

                        window.Echo = new Echo({
                            broadcaster: 'reverb',
                            key: '{{ config('broadcasting.connections.reverb.key') }}',
                            wsHost: '{{ config('broadcasting.connections.reverb.options.host') }}',
                            wsPort: 8085, // HARDCODED FIX
                            wssPort: 8085, // HARDCODED FIX
                            forceTLS: false, // HARDCODED FIX
                            enabledTransports: ['ws', 'wss'],
                        });

                        console.log('Kiosk: Listening for events on reception channel...');
                        window.Echo.channel('reception')
                            .listen('.BadgeScanned', (e) => this.handleRemoteScan(e))
                            .listen('BadgeScanned', (e) => this.handleRemoteScan(e));
                    };

                    const checkScripts = setInterval(() => {
                        if (window.Echo && window.Pusher) {
                            clearInterval(checkScripts);
                            initEcho();
                        }
                    }, 500);
                },

                updateTime() {
                    this.currentTime = new Date().toLocaleTimeString('fr-FR', { 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                },

                submitGroupCheck() {
                    if (!this.currentBadge) return;
                    
                    fetch('{{ route('reception.checkin.group') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            badge_uid: this.currentBadge,
                            attendees: this.countInput
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                             // Success is handled by broadcast usually, but we can set it here too to be snappy
                             this.state = 'success';
                             this.serverMessage = data.message;
                        } else {
                             this.state = 'error';
                             this.serverMessage = data.message;
                        }
                        this.countInput = 1;
                        this.currentBadge = null;
                        this.autoReset();
                    })
                    .catch(e => {
                        console.error(e);
                        this.state = 'error';
                        this.serverMessage = "Erreur de connexion";
                        this.autoReset();
                    });
                },

                handleRemoteScan(data) {
                    if (this.state === 'success' || this.state === 'error') {
                        if (this.resetTimer) clearTimeout(this.resetTimer);
                    }

                    // GROUP CHECK-IN FLOW
                    if (data.action === 'request_count') {
                        this.state = 'group_checkin';
                        this.currentBadge = data.badge_uid;
                        this.profile = data.person;
                        this.countInput = 1;
                        // Don't auto-reset while typing
                        if (this.resetTimer) clearTimeout(this.resetTimer);
                        return;
                    }

                    this.state = data.decision === 'granted' ? 'success' : 'error';
                    this.serverMessage = data.decision === 'granted' 
                        ? (data.reason || 'Accès Autorisé') 
                        : (data.reason || 'Accès Refusé');
                    
                    if (data.person) {
                        this.profile = data.person;
                    }

                    this.autoReset();
                },

                autoReset() {
                    if (this.resetTimer) clearTimeout(this.resetTimer);
                    this.resetTimer = setTimeout(() => {
                        this.state = 'idle';
                    }, 4000);
                }
            }));
        });
    </script>
</body>
</html>
