// INNOVA-STEAM Service Worker — caché del armazón offline
//
// La app se despliega bajo un subdirectorio (/innovasteam), así que las
// rutas del armazón no pueden ser absolutas desde la raíz del dominio:
// se derivan de la ubicación del propio service worker. Antes apuntaban
// a '/assets/...' y en producción nunca llegaban a cachearse.
const CACHE = 'innova-steam-v2';

// '/innovasteam/sw.js' → '/innovasteam'
const BASE = self.location.pathname.replace(/\/sw\.js$/, '');

const SHELL = [
    `${BASE}/`,
    `${BASE}/offline.html`,
    `${BASE}/assets/css/stellar.css`,
    `${BASE}/assets/css/sidebar.css`,
    `${BASE}/assets/css/motion.css`,
    `${BASE}/assets/js/main.js`,
    `${BASE}/assets/js/ui.js`,
    // Librerías servidas en local: sin ellas, sin conexión se pierden
    // los iconos, los desplegables y los gráficos.
    `${BASE}/assets/vendor/alpine.min.js`,
    `${BASE}/assets/vendor/lucide.min.js`,
    `${BASE}/assets/vendor/chart.umd.min.js`,
    `${BASE}/stellarscribe/portal.php`,
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c =>
            // addAll() aborta entero si un solo recurso falla. Se cachea
            // uno a uno para que un 404 puntual no deje la app sin
            // armazón offline.
            Promise.all(SHELL.map(url =>
                c.add(new Request(url, { cache: 'reload' })).catch(() => {})
            ))
        )
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

    // Solo GET: nunca se cachea un envío de formulario ni un login.
    if (request.method !== 'GET') return;
    if (!request.url.startsWith('http')) return;

    // Las respuestas de la API cambian con cada acción; servir una
    // versión cacheada mostraría progreso o notificaciones obsoletos.
    if (request.url.includes('/api/')) return;

    e.respondWith(
        fetch(request)
            .then(res => {
                const cacheable = res.ok && (
                    request.mode === 'navigate' ||
                    request.destination === 'style' ||
                    request.destination === 'script' ||
                    request.destination === 'font'
                );
                if (cacheable) {
                    const clone = res.clone();
                    caches.open(CACHE).then(c => c.put(request, clone));
                }
                return res;
            })
            .catch(() =>
                caches.match(request).then(cached =>
                    cached || caches.match(`${BASE}/offline.html`)
                )
            )
    );
});
