/**
 * EMSP Docs PWA — installation native uniquement (pas de bannière / guide custom).
 *
 * Debug checklist installabilité Chrome :
 * 1. manifest.json valide (Content-Type application/manifest+json)
 * 2. Service worker /sw.js enregistré avec scope /
 * 3. HTTPS (unaux.com)
 * 4. link rel=manifest sur chaque layout (emsp_can_expose_manifest)
 * 5. Icons 192 + 512 sans 404
 * 6. start_url dans scope (./ relatif au manifest)
 * 7. NE PAS appeler preventDefault() sur beforeinstallprompt
 * 8. fetch handler + install/activate dans sw.js
 */
(function () {
  'use strict';

  function resolveAppBase() {
    if (window.__emspPwaConfig && window.__emspPwaConfig.basePath) {
      var configured = String(window.__emspPwaConfig.basePath);
      return configured.endsWith('/') ? configured : configured + '/';
    }

    var manifest = document.querySelector('link[rel="manifest"]');
    if (manifest && manifest.href) {
      try {
        var fromManifest = new URL('.', manifest.href).pathname;
        return fromManifest.endsWith('/') ? fromManifest : fromManifest + '/';
      } catch (err) {
        console.warn('[PWA] Impossible de déduire basePath depuis le manifest:', err);
      }
    }

    var pathname = window.location.pathname || '/';
    if (pathname.endsWith('/')) {
      return pathname;
    }
    var lastSlash = pathname.lastIndexOf('/');
    return lastSlash > 0 ? pathname.substring(0, lastSlash + 1) : '/';
  }

  function resolveSwUrl() {
    if (window.__emspPwaConfig && window.__emspPwaConfig.swUrl) {
      try {
        return new URL(String(window.__emspPwaConfig.swUrl), window.location.origin).href;
      } catch (err) {
        console.warn('[PWA] swUrl config invalide, fallback:', err);
      }
    }
    return new URL('sw.js', window.location.origin + resolveAppBase()).href;
  }

  function registerServiceWorker() {
    if (!('serviceWorker' in navigator)) {
      console.info('[PWA] Service Worker non supporté par ce navigateur.');
      return;
    }

    var basePath = resolveAppBase();
    var swUrl = resolveSwUrl();
    var scope = basePath;

    if (scope === '/' || scope === '//') {
      scope = '/';
    }

    console.info('[PWA] Enregistrement SW…', { swUrl: swUrl, scope: scope });

    navigator.serviceWorker.register(swUrl, { scope: scope })
      .then(function (registration) {
        console.info('[PWA] SW registered:', registration.scope, '(script:', swUrl + ')');
      })
      .catch(function (err) {
        console.error('[PWA] SW registration failed:', err, { swUrl: swUrl, scope: scope });
      });
  }

  var isStandalone = !!(
    (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches)
    || window.navigator.standalone === true
  );

  if (isStandalone) {
    document.documentElement.classList.add('pwa-installed');
  }

  window.addEventListener('appinstalled', function () {
    document.documentElement.classList.add('pwa-installed');
    console.info('[PWA] Application installée.');
  });

  // Laisser Chrome/Edge/Samsung Internet gérer l'UI native — pas de preventDefault().
  window.addEventListener('beforeinstallprompt', function () {
    console.info('[PWA] beforeinstallprompt — prompt natif du navigateur autorisé.');
  });

  registerServiceWorker();

  window.emspPwa = {
    isStandalone: function () {
      return isStandalone;
    }
  };
})();
