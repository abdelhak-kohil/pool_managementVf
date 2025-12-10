<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borne d'Accès - Scan</title>
    <script src="{{ asset('vendor/tailwindcss/tailwindcss.js') }}"></script>
    <script src="{{ asset('vendor/alpine/alpine.min.js') }}" defer></script>
    <link href="{{ asset('vendor/fonts/dm-sans.css') }}" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="bg-slate-100 h-screen flex flex-col items-center justify-center relative overflow-hidden" x-data="kioskScanner()" x-init="initScanner()">

    <!-- Abstract Background -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[600px] h-[600px] bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-float"></div>
        <div class="absolute -bottom-40 -left-40 w-[600px] h-[600px] bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-float" style="animation-delay: 2s"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] bg-indigo-100 rounded-full mix-blend-multiply filter blur-3xl opacity-50 animate-float" style="animation-delay: 4s"></div>
    </div>

    <!-- Navbar -->
    <nav class="absolute top-0 w-full p-6 flex justify-between items-center z-20">
        <div class="flex items-center gap-3 bg-white/50 backdrop-blur-md px-4 py-2 rounded-full border border-white/50 shadow-sm">
            <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            <span class="text-slate-600 font-medium text-sm">Système en ligne</span>
        </div>
        <a href="{{ route('reception.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-slate-800 transition-colors bg-white/50 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/50 shadow-sm hover:shadow-md hover:bg-white">
            <span class="text-sm font-bold">Quitter</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
        </a>
    </nav>

    <!-- Main Card -->
    <main class="w-full max-w-xl z-10 px-4">
        <div class="glass rounded-3xl shadow-2xl border border-white/50 p-8 md:p-12 text-center transition-all duration-500 transform"
             :class="{
                'scale-105 shadow-green-200/50 border-green-200': state === 'success',
                'scale-105 shadow-red-200/50 border-red-200': state === 'error'
             }">
            
            <!-- Dynamic Icon Area -->
            <div class="mb-10 relative h-32 flex items-center justify-center">
                
                <!-- Idle: Animated Scan Icon -->
                <div x-show="state === 'idle'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-50" x-transition:enter-end="opacity-100 scale-100" class="absolute">
                    <div class="relative">
                        <div class="w-28 h-28 bg-blue-50 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div class="absolute inset-0 border-2 border-blue-500 rounded-full opacity-20 animate-ping"></div>
                    </div>
                </div>

                <!-- Processing: Spinner -->
                <div x-show="state === 'processing'" x-cloak class="absolute">
                    <div class="w-28 h-28 bg-white rounded-full flex items-center justify-center shadow-inner">
                        <div class="w-16 h-16 border-4 border-blue-100 border-t-blue-600 rounded-full animate-spin"></div>
                    </div>
                </div>

                <!-- Success: Checkmark -->
                <div x-show="state === 'success'" x-cloak class="absolute">
                    <div class="w-32 h-32 bg-green-100 rounded-full flex items-center justify-center animate-[bounce_1s_ease-in-out]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <!-- Error: X Mark -->
                <div x-show="state === 'error'" x-cloak class="absolute">
                    <div class="w-32 h-32 bg-red-100 rounded-full flex items-center justify-center animate-[shake_0.5s_ease-in-out]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Status Text -->
            <div class="mb-10 transition-all duration-500 ease-out transform"
                 :class="{
                    'bg-emerald-50 border-emerald-100 p-6 rounded-2xl border shadow-sm scale-105': state === 'success',
                    'bg-rose-50 border-rose-100 p-6 rounded-2xl border shadow-sm scale-105': state === 'error',
                    'py-2': state === 'idle' || state === 'processing'
                 }">
                <h1 class="text-3xl font-bold tracking-tight transition-colors duration-300" 
                    :class="{
                        'text-slate-800': state === 'idle' || state === 'processing',
                        'text-emerald-700': state === 'success',
                        'text-rose-700': state === 'error'
                    }"
                    x-text="getTitle()"></h1>
                <p class="text-lg font-medium mt-2 transition-colors duration-300" 
                   :class="{
                        'text-slate-500': state === 'idle' || state === 'processing',
                        'text-emerald-600': state === 'success',
                        'text-rose-600': state === 'error'
                   }"
                   x-text="getMessage()"></p>
            </div>

            <!-- Input Area -->
            <div class="relative max-w-sm mx-auto group">
                <div class="relative transition-all duration-300 transform group-focus-within:scale-105">
                    <input 
                        x-ref="scanInput"
                        id="scanInput"
                        type="text" 
                        x-model="badgeUid" 
                        @keydown.enter="processScan()"
                        class="w-full bg-white border-0 text-slate-800 text-lg rounded-full py-4 pl-12 pr-6 focus:ring-4 focus:ring-blue-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.1)] placeholder:text-slate-300 font-medium tracking-wide transition-all"
                        placeholder="Scanner ou saisir ID..."
                        autocomplete="off"
                    >
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-300 group-focus-within:text-blue-500 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                        </svg>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="absolute bottom-6 text-center w-full z-10">
        <p class="text-slate-400 text-sm font-medium">PoolManager &copy; {{ date('Y') }}</p>
    </footer>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('kioskScanner', () => ({
                state: 'idle', // idle, processing, success, error
                badgeUid: '',
                serverMessage: '',
                resetTimer: null,

                initScanner() {
                    this.focusInput();
                    document.addEventListener('click', (e) => {
                        // Only refocus if not clicking a link or the input itself
                        if(e.target.tagName !== 'A' && e.target.tagName !== 'INPUT') this.focusInput();
                    });
                },

                focusInput() {
                    this.$nextTick(() => {
                        this.$refs.scanInput.focus();
                    });
                },

                getTitle() {
                    switch(this.state) {
                        case 'idle': return 'Scanner votre Badge';
                        case 'processing': return 'Vérification...';
                        case 'success': return 'Bienvenue !';
                        case 'error': return 'Accès Refusé';
                    }
                },

                getMessage() {
                    if (this.state === 'idle') return 'Approchez le badge du lecteur ou saisissez l\'ID ci-dessous.';
                    if (this.state === 'processing') return 'Nous vérifions vos droits d\'accès.';
                    return this.serverMessage;
                },

                processScan() {
                    if (!this.badgeUid.trim() || this.state === 'processing') return;

                    const scannedUid = this.badgeUid;
                    this.badgeUid = ''; 
                    this.state = 'processing';
                    
                    if (this.resetTimer) clearTimeout(this.resetTimer);

                    fetch('{{ route("reception.checkin.badge") }}', {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                        },
                        body: JSON.stringify({ badge_uid: scannedUid })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.state = data.success ? 'success' : 'error';
                        this.serverMessage = data.message;
                        
                        this.resetTimer = setTimeout(() => {
                            this.state = 'idle';
                            this.serverMessage = '';
                        }, 3500);
                    })
                    .catch(err => {
                        console.error('Scan error:', err);
                        this.state = 'error';
                        this.serverMessage = 'Erreur de connexion au serveur.';
                        this.resetTimer = setTimeout(() => {
                            this.state = 'idle';
                        }, 3500);
                    });
                }
            }));
        });
    </script>
</body>
</html>
