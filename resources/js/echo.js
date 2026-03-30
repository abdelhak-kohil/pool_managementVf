import Echo from 'laravel-echo';

//import Echo from 'laravel-echo';

// Pusher loaded globally via script tag to avoid build error
// window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT || 8085,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8085,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
});
