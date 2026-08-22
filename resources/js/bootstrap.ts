import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const reverbHost = import.meta.env.VITE_REVERB_HOST;

const isLocalhost = (host: string): boolean =>
    host === 'localhost' || host === '127.0.0.1' || host === '[::1]';

const currentHost = window.location.hostname;

// No inicializar Echo en producción si el host de Reverb sigue siendo localhost,
// para evitar conexiones fallidas repetidas a localhost:8080 en el entorno demo.
const shouldInitializeEcho =
    reverbHost && !(isLocalhost(reverbHost) && !isLocalhost(currentHost));

if (shouldInitializeEcho) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: reverbHost,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
    });
}
