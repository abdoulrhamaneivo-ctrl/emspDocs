(function (window, document) {
  'use strict';

  var ROOT_ID = 'emsp-flash-root';
  var LEGACY_ROOT_ID = 'flash-container';

  var ICONS = {
    success: 'bi-check-circle-fill',
    error: 'bi-exclamation-circle-fill',
    danger: 'bi-exclamation-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    info: 'bi-info-circle-fill'
  };

  var AUTO_DISMISS_MS = 4500;
  var TRANSITION_MS = 260;
  var payloadConsumed = false;

  function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function normalizeType(type) {
    var value = String(type || 'info').toLowerCase();
    if (value === 'danger') {
      return 'error';
    }
    return ['success', 'error', 'warning', 'info'].indexOf(value) >= 0 ? value : 'info';
  }

  function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = String(text || '');
    return div.innerHTML;
  }

  function ensureContainer() {
    var container = document.getElementById(ROOT_ID) || document.getElementById(LEGACY_ROOT_ID);
    if (container) {
      if (container.id === LEGACY_ROOT_ID) {
        container.id = ROOT_ID;
      }
      return container;
    }

    container = document.createElement('div');
    container.id = ROOT_ID;
    container.setAttribute('role', 'region');
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-label', 'Notifications');
    document.body.appendChild(container);
    return container;
  }

  function dismissFlash(node) {
    if (!node || node.dataset.emspDismissing === '1') {
      return;
    }
    node.dataset.emspDismissing = '1';
    if (node._emspTimer) {
      window.clearTimeout(node._emspTimer);
    }

    if (prefersReducedMotion()) {
      node.remove();
      return;
    }

    node.style.opacity = '0';
    node.style.transform = 'translateX(12px)';
    window.setTimeout(function () {
      if (node.parentNode) {
        node.parentNode.removeChild(node);
      }
    }, TRANSITION_MS);
  }

  function showFlash(data) {
    var type = normalizeType(data && data.type);
    var title = (data && data.title) ? String(data.title).trim() : '';
    var message = (data && data.message) ? String(data.message).trim() : '';
    var actionUrl = (data && data.action_url) ? String(data.action_url) : '';
    var actionLabel = (data && data.action_label) ? String(data.action_label) : '';
    var displayTitle = title || message;
    var displayMessage = title ? message : '';
    var container = ensureContainer();
    var flash = document.createElement('div');
    var iconClass = ICONS[type] || ICONS.info;

    flash.className = 'emsp-flash emsp-flash--' + type;
    flash.setAttribute('role', 'alert');
    flash.innerHTML =
      '<span class="emsp-flash__icon"><i class="bi ' + iconClass + '" aria-hidden="true"></i></span>' +
      '<div class="emsp-flash__body">' +
        (displayTitle ? '<div class="emsp-flash__title">' + escapeHtml(displayTitle) + '</div>' : '') +
        (displayMessage ? '<div class="emsp-flash__msg">' + escapeHtml(displayMessage) + '</div>' : '') +
        (actionUrl && actionLabel
          ? '<a class="emsp-flash__action" href="' + escapeHtml(actionUrl) + '">' + escapeHtml(actionLabel) + '</a>'
          : '') +
      '</div>' +
      '<button type="button" class="emsp-flash__close" aria-label="Fermer la notification">' +
        '<i class="bi bi-x-lg" aria-hidden="true"></i>' +
      '</button>';

    if (prefersReducedMotion()) {
      flash.classList.add('emsp-flash--reduced');
    } else {
      flash.classList.add('emsp-flash--entering');
      window.requestAnimationFrame(function () {
        flash.classList.remove('emsp-flash--entering');
        flash.classList.add('emsp-flash--entered');
      });
    }

    flash.querySelector('.emsp-flash__close').addEventListener('click', function () {
      dismissFlash(flash);
    });

    container.appendChild(flash);

    if (type !== 'error') {
      flash._emspTimer = window.setTimeout(function () {
        dismissFlash(flash);
      }, AUTO_DISMISS_MS);
    }

    return flash;
  }

  function readPayload() {
    var node = document.getElementById('emsp-flash-payload');
    if (!node) {
      return [];
    }
    try {
      var payload = JSON.parse(node.textContent || '[]');
      node.parentNode && node.parentNode.removeChild(node);
      return Array.isArray(payload) ? payload : [];
    } catch (error) {
      return [];
    }
  }

  function replayPayload() {
    if (payloadConsumed) {
      return;
    }
    payloadConsumed = true;

    var flashes = readPayload();
    flashes.forEach(function (flash, index) {
      window.setTimeout(function () {
        showFlash(flash);
      }, index * 130);
    });
  }

  window.emspFlash = {
    show: showFlash,
    replay: replayPayload,
    get consumed() {
      return payloadConsumed;
    }
  };

  function init() {
    ensureContainer();
    replayPayload();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
