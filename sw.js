/* EMSP Docs Service Worker — resilient PWA shell */
// Cache version also invalidates retired stylesheet/font responses.
const CACHE_VERSION = 'emsp-shell-20260814ah';
const OFFLINE_URL = './offline.php';

function basePath() {
  return new URL(self.registration.scope).pathname.replace(/\/$/, '');
}

function shellUrls() {
  const base = basePath();
  return [
    `${base}/index.php`,
    `${base}/documents`,
    `${base}/formations`,
    `${base}/journal`,
    `${base}/offline.php`,
    `${base}/manifest.json`,
    `${base}/assets/css/emsp-app.css`,
    `${base}/assets/css/emsp-theme.css`,
    `${base}/assets/css/emsp-fixes.css`,
    `${base}/assets/css/emsp-editorial-shell.css`,
    `${base}/assets/css/docs-workspace.css`,
    `${base}/assets/css/emsp-fonts.css`,
    `${base}/assets/js/bootstrap5.bundle.min.js`,
    `${base}/assets/js/emsp-admin-shell.js`,
    `${base}/assets/js/emsp-modal-guard.js`,
    `${base}/assets/js/emsp-pdf-preview.js`,
    `${base}/assets/js/docs-workspace.js`,
    `${base}/assets/js/emsp-mobile-fab.js`,
    `${base}/assets/js/emsp-scanner.js`,
    `${base}/assets/js/pwa.js`,
    `${base}/assets/images/logo-emsp-192.png`,
    `${base}/assets/images/logo-emsp-512.png`
  ];
}

function isValidCacheResponse(response) {
  return !!response && response.ok && response.type !== 'opaque';
}

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_VERSION)
      .then(cache => Promise.allSettled(shellUrls().map(url => cache.add(url))))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys()
      .then(keys => Promise.all(
        keys.filter(key => key !== CACHE_VERSION).map(key => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

function isSameOrigin(url) {
  return url.origin === self.location.origin;
}

function isExcluded(url) {
  const base = basePath();
  return (
    url.pathname.startsWith(`${base}/admin/`) ||
    url.pathname.startsWith(`${base}/telecharger`) ||
    url.pathname.startsWith(`${base}/push-`) ||
    url.pathname.includes('/api/')
  );
}

async function offlineFallback() {
  const cache = await caches.open(CACHE_VERSION);
  const offline = await cache.match(OFFLINE_URL);
  if (offline) {
    return offline;
  }
  return new Response('Offline', {
    status: 503,
    statusText: 'Service Unavailable',
    headers: { 'Content-Type': 'text/plain; charset=UTF-8' }
  });
}

async function networkFirst(request) {
  const cache = await caches.open(CACHE_VERSION);
  try {
    const response = await fetch(request);
    if (isValidCacheResponse(response)) {
      await cache.put(request, response.clone());
    }
    if (response) {
      return response;
    }
  } catch (error) {
    // Fall through to cache/offline.
  }

  const cached = await cache.match(request);
  if (cached) {
    return cached;
  }

  if (request.mode === 'navigate' || request.destination === 'document') {
    return offlineFallback();
  }

  return Response.error();
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(CACHE_VERSION);
  const cached = await cache.match(request);

  try {
    const response = await fetch(request);
    if (isValidCacheResponse(response)) {
      await cache.put(request, response.clone());
    }
    return cached || response || Response.error();
  } catch (error) {
    return cached || Response.error();
  }
}

async function networkPassthrough(request) {
  try {
    const response = await fetch(request);
    if (response) {
      return response;
    }
  } catch (error) {
    // Fall through to cache.
  }

  const cache = await caches.open(CACHE_VERSION);
  const cached = await cache.match(request);
  return cached || Response.error();
}

self.addEventListener('fetch', event => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  if (!isSameOrigin(url) || isExcluded(url)) {
    return;
  }

  // HTML/navigation: prefer fresh content, fall back to cache/offline.
  if (request.mode === 'navigate' || request.destination === 'document') {
    event.respondWith(networkFirst(request));
    return;
  }

  // Static assets: cached response first, then refresh in background.
  if (['style', 'script', 'image', 'font'].includes(request.destination)) {
    event.respondWith(staleWhileRevalidate(request));
    return;
  }

  event.respondWith(networkPassthrough(request));
});

self.addEventListener('push', event => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = { title: 'EMSP Docs', body: event.data ? event.data.text() : '' };
  }

  const base = basePath();
  const title = data.title || 'EMSP Docs';
  const options = {
    body: data.body || '',
    icon: data.icon || `${base}/assets/images/logo-emsp-192.png`,
    badge: data.badge || `${base}/assets/images/logo-emsp-192.png`,
    data: { url: data.url || `${base}/index.php` },
    vibrate: [100, 50, 100]
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
  event.notification.close();
  const base = basePath();
  const target = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : `${base}/index.php`;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
      for (const client of clientList) {
        if ('focus' in client && client.url === target) return client.focus();
      }
      if (clients.openWindow) return clients.openWindow(target);
    })
  );
});
