const CACHE_NAME = 'sabere-v1';
const STATIC_CACHE = 'sabere-static-v1';
const DYNAMIC_CACHE = 'sabere-dynamic-v1';
const OFFLINE_QUEUE_KEY = 'sabere-offline-queue';

// Archivos estáticos para cachear
const STATIC_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192x192.png',
    '/icons/icon-512x512.png',
    '/icons/icon-72x72.png',
    '/build/manifest.json',
];

// Instalar Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .catch((err) => {
                console.log('[SW] Error caching static assets:', err);
            })
    );
    self.skipWaiting();
});

// Activar Service Worker
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        })
    );
    self.clients.claim();
});

// Estrategia de caché: Network First con fallback a cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorar peticiones a APIs externas
    if (!url.origin.includes(self.location.origin)) {
        return;
    }

    // Para peticiones de escritura, intentar network y guardar en cola si falla
    if (request.method !== 'GET') {
        event.respondWith(handleMutation(request));
        return;
    }

    // Para peticiones de API, usar Network First
    if (url.pathname.startsWith('/api/')) {
        event.respondWith(networkFirst(request));
        return;
    }

    // Para assets estáticos, usar Cache First
    if (url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff|woff2)$/)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Para navegación, usar Network First con fallback offline
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .catch(() => caches.match('/offline.html'))
        );
        return;
    }

    // Default: Network First
    event.respondWith(networkFirst(request));
});

// Cache First Strategy
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Network First Strategy
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        return new Response('Offline', { status: 503 });
    }
}

// Manejar mutaciones POST/PUT/DELETE offline
async function handleMutation(request) {
    try {
        return await fetch(request);
    } catch (error) {
        const clone = request.clone();
        const payload = await clone.text();

        const queueEntry = {
            url: request.url,
            method: request.method,
            headers: Array.from(request.headers.entries()),
            body: payload,
            timestamp: Date.now(),
        };

        const queue = await getOfflineQueue();
        queue.push(queueEntry);
        await saveOfflineQueue(queue);

        return new Response(JSON.stringify({ queued: true }), {
            status: 202,
            headers: { 'Content-Type': 'application/json' },
        });
    }
}

async function getOfflineQueue() {
    const allClients = await self.clients.matchAll({ type: 'window' });
    if (allClients.length > 0) {
        // En una implementación completa se usaría IndexedDB
        return JSON.parse(localStorage?.getItem(OFFLINE_QUEUE_KEY) || '[]');
    }
    return [];
}

async function saveOfflineQueue(queue) {
    // Stub: en la implementación completa se persistiría en IndexedDB
    console.log('[SW] Queued offline action:', queue);
}

// Manejar notificaciones push
self.addEventListener('push', (event) => {
    const data = event.data?.json() || {};
    const title = data.title || 'Saberé';
    const options = {
        body: data.body || 'Tienes una nueva notificación',
        icon: data.icon || '/icons/icon-192x192.png',
        badge: data.badge || '/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
            type: data.type || 'notification',
        },
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Manejar clic en notificación
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then((clientList) => {
            for (const client of clientList) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
