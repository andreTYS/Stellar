// INNOVA-STEAM Service Worker — offline shell cache
const CACHE = 'innova-steam-v1';
const SHELL = [
    '/',
    '/assets/css/stellar.css',
    '/assets/css/sidebar.css',
    '/assets/js/main.js',
    '/stellarscribe/portal.php',
    '/stellarscribe/simuladores/',
    '/offline.html',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(SHELL.map(u => new Request(u, { cache: 'reload' }))))
    );
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    const { request } = e;
    // Only cache GET, skip auth/POST
    if (request.method !== 'GET') return;
    // Skip chrome-extension and non-http
    if (!request.url.startsWith('http')) return;

    e.respondWith(
        fetch(request)
            .then(res => {
                // Cache successful navigations + static assets
                if (res.ok && (request.mode === 'navigate' || request.destination === 'style' || request.destination === 'script')) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(request, clone));
                }
                return res;
            })
            .catch(() => caches.match(request).then(cached => cached || caches.match('/offline.html')))
    );
});
