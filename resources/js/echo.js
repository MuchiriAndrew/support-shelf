import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const runtimeReverb = window.supportShelfConfig?.reverb || {};
const key = runtimeReverb.appKey || import.meta.env.VITE_REVERB_APP_KEY;
const realtimeEnabled = document.documentElement.dataset.realtime === 'true';

if (key && realtimeEnabled) {
    window.Pusher = Pusher;

    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: runtimeReverb.host || import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: Number(runtimeReverb.port || import.meta.env.VITE_REVERB_PORT || 8080),
        wssPort: Number(runtimeReverb.port || import.meta.env.VITE_REVERB_PORT || 443),
        forceTLS: (runtimeReverb.scheme || import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
