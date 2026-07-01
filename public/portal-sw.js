const CACHE = 'member-portal-shell-v1';

const SHELL = [
    '/portal/login',
    '/manifest.json',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(SHELL)).catch(() => undefined),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))),
        ),
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (
        url.pathname.startsWith('/livewire/') ||
        url.pathname.startsWith('/filament/') ||
        url.pathname.includes('broadcasting')
    ) {
        return;
    }

    if (! url.pathname.startsWith('/portal')) {
        return;
    }

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request)),
    );
});
