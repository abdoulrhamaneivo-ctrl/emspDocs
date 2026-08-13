(function () {
  'use strict';

  var config = window.__emspNotifConfig || {};
  if (!config.authenticated || !config.unreadUrl) {
    return;
  }

  var POLL_MS = Math.max(30000, parseInt(config.pollInterval, 10) || 60000);
  var timerId = null;
  var inflight = false;
  var lastCount = null;
  var panel = null;
  var backdrop = null;
  var panelOpen = false;
  var activeTrigger = null;
  var recentInflight = false;
  var closeTimerId = null;
  var MOBILE_MQ = window.matchMedia('(max-width: 767.98px)');
  var JOURNAL_TYPES = ['journal_published', 'journal_liked', 'journal_commented'];

  function formatBadge(count) {
    if (count <= 0) {
      return '';
    }
    return count > 99 ? '99+' : String(count);
  }

  function ariaSuffix(count) {
    if (count <= 0) {
      return '';
    }
    var label = count > 99 ? '99+' : String(count);
    return ' (' + label + ' non lues)';
  }

  function getPanel() {
    if (!panel) {
      panel = document.getElementById('emspNotifPanel');
    }
    return panel;
  }

  function ensurePanelInBody() {
    var p = getPanel();
    if (p && p.parentNode !== document.body) {
      document.body.appendChild(p);
    }
    return p;
  }

  function resetPanelPresentation() {
    var p = getPanel();
    if (!p) {
      return;
    }

    p.style.position = '';
    p.style.top = '';
    p.style.left = '';
    p.style.right = '';
    p.style.bottom = '';
    p.style.width = '';
    p.style.zIndex = '';
    p.classList.remove('is-mobile-sheet', 'is-open', 'is-closing');
    p.setAttribute('aria-modal', 'false');
  }

  function queryPanel(sel) {
    var p = getPanel();
    return p ? p.querySelector(sel) : null;
  }

  function updateBadges(count) {
    var text = formatBadge(count);
    var triggers = document.querySelectorAll('[data-emsp-notif-trigger]');

    triggers.forEach(function (trigger) {
      if (trigger.classList.contains('emsp-open-login-modal')) {
        return;
      }

      var badge = trigger.querySelector('.emsp-navbar-notifications__badge');
      var baseLabel = trigger.getAttribute('data-emsp-notif-label') || 'Notifications';

      if (count > 0) {
        trigger.classList.add('has-unread');
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'emsp-navbar-notifications__badge';
          trigger.appendChild(badge);
        }
        badge.textContent = text;
        badge.hidden = false;
      } else {
        trigger.classList.remove('has-unread');
        if (badge) {
          badge.remove();
        }
      }

      trigger.setAttribute('aria-label', baseLabel + ariaSuffix(count));
      trigger.setAttribute('title', baseLabel + ariaSuffix(count));
    });

    var markAllBtn = queryPanel('[data-emsp-notif-mark-all]');
    if (markAllBtn) {
      markAllBtn.hidden = count <= 0;
    }

    var dashCount = document.querySelector('[data-emsp-notif-dashboard-count]');
    if (dashCount) {
      dashCount.textContent = String(count);
    }

    document.dispatchEvent(new CustomEvent('emsp:notifications:count', {
      detail: { count: count }
    }));
  }

  function timeAgo(isoString) {
    var date = new Date(isoString);
    if (isNaN(date.getTime())) {
      return isoString || '';
    }

    var diffMs = Date.now() - date.getTime();
    var mins = Math.floor(diffMs / 60000);
    if (mins < 1) {
      return "À l'instant";
    }
    if (mins < 60) {
      return 'Il y a ' + mins + ' min';
    }
    var hours = Math.floor(mins / 60);
    if (hours < 24) {
      return 'Il y a ' + hours + ' h';
    }
    var days = Math.floor(hours / 24);
    if (days < 7) {
      return 'Il y a ' + days + ' j';
    }
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }

  function resolveHref(link) {
    var target = String(link || '').trim();
    if (!target) {
      return '';
    }
    if (/^https?:\/\//i.test(target) || target.charAt(0) === '/') {
      return target;
    }
    var base = config.appBase || './';
    return base + target.replace(/^\//, '');
  }

  function setPanelLoading(isLoading) {
    var loading = queryPanel('[data-emsp-notif-loading]');
    var list = queryPanel('[data-emsp-notif-list]');
    var empty = queryPanel('[data-emsp-notif-empty]');
    if (loading) {
      loading.hidden = !isLoading;
    }
    if (isLoading && list) {
      list.hidden = true;
    }
    if (isLoading && empty) {
      empty.hidden = true;
    }
  }

  function renderRecentItems(items, unread) {
    var list = queryPanel('[data-emsp-notif-list]');
    var empty = queryPanel('[data-emsp-notif-empty]');
    if (!list || !empty) {
      return;
    }

    list.innerHTML = '';

    if (!items || !items.length) {
      list.hidden = true;
      empty.hidden = false;
      updateBadges(typeof unread === 'number' ? unread : (lastCount || 0));
      return;
    }

    empty.hidden = true;
    list.hidden = false;

    items.forEach(function (item) {
      var li = document.createElement('li');
      li.className = 'emsp-notif-item' + (item.is_read ? '' : ' emsp-notif-item--unread');
      li.setAttribute('role', 'listitem');

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'emsp-notif-item__btn';
      btn.dataset.notificationId = String(item.id || 0);
      btn.dataset.redirectTo = String(item.link || '');

      var iconWrap = document.createElement('span');
      iconWrap.className = 'emsp-notif-item__icon ' + (item.icon_color || 'is-muted');
      iconWrap.setAttribute('aria-hidden', 'true');
      iconWrap.innerHTML = '<i class="bi ' + (item.icon || 'bi-bell-fill') + '"></i>';

      var body = document.createElement('span');
      body.className = 'emsp-notif-item__body';

      var message = document.createElement('span');
      message.className = 'emsp-notif-item__message';
      message.textContent = item.message || item.type_label || 'Notification';

      body.appendChild(message);

      var type = String(item.type || '').toLowerCase();
      if (item.doc_title && JOURNAL_TYPES.indexOf(type) === -1) {
        var doc = document.createElement('span');
        doc.className = 'emsp-notif-item__doc';
        doc.textContent = item.doc_title;
        body.appendChild(doc);
      }

      var time = document.createElement('time');
      time.className = 'emsp-notif-item__time';
      time.dateTime = item.created_at || '';
      time.textContent = timeAgo(item.created_at);
      body.appendChild(time);

      if (!item.is_read) {
        var dot = document.createElement('span');
        dot.className = 'emsp-notif-item__dot';
        dot.setAttribute('aria-hidden', 'true');
        btn.appendChild(iconWrap);
        btn.appendChild(body);
        btn.appendChild(dot);
      } else {
        btn.appendChild(iconWrap);
        btn.appendChild(body);
      }

      btn.addEventListener('click', function () {
        markOneRead(item.id, item.link, li);
      });

      li.appendChild(btn);
      list.appendChild(li);
    });

    if (typeof unread === 'number') {
      lastCount = unread;
      updateBadges(unread);
    }
  }

  function fetchRecentList() {
    if (!config.recentUrl || recentInflight) {
      return;
    }

    recentInflight = true;
    setPanelLoading(true);

    fetch(config.recentUrl + '?limit=10', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (response.status === 401) {
          stopPolling();
          closePanel();
          return null;
        }
        if (!response.ok) {
          throw new Error('recent failed');
        }
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.ok) {
          return;
        }
        renderRecentItems(payload.items || [], payload.unread);
      })
      .catch(function () {
        var empty = queryPanel('[data-emsp-notif-empty]');
        var list = queryPanel('[data-emsp-notif-list]');
        if (empty) {
          empty.hidden = false;
        }
        if (list) {
          list.hidden = true;
        }
      })
      .finally(function () {
        recentInflight = false;
        setPanelLoading(false);
      });
  }

  function fetchUnreadCount() {
    if (inflight || document.hidden) {
      return;
    }

    inflight = true;

    fetch(config.unreadUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (response.status === 401) {
          stopPolling();
          return null;
        }
        if (!response.ok) {
          throw new Error('unread-count failed');
        }
        return response.json();
      })
      .then(function (payload) {
        if (!payload || typeof payload.unread !== 'number') {
          return;
        }
        var count = Math.max(0, payload.unread);
        if (lastCount === null || lastCount !== count) {
          lastCount = count;
          updateBadges(count);
        }
      })
      .catch(function () {
        /* Non bloquant */
      })
      .finally(function () {
        inflight = false;
      });
  }

  function postMarkRead(formData) {
    if (config.csrfToken && !formData.get('csrf_token')) {
      formData.append('csrf_token', config.csrfToken);
    }

    return fetch(config.markReadUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': config.csrfToken || ''
      },
      body: formData
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('mark-read failed');
        }
        return response.json();
      })
      .then(function (payload) {
        if (!payload || !payload.ok) {
          throw new Error('mark-read rejected');
        }
        return payload;
      });
  }

  function markOneRead(notificationId, redirectTo, listItem) {
    var formData = new FormData();
    formData.append('notification_id', String(notificationId || 0));
    formData.append('redirect_to', String(redirectTo || ''));

    postMarkRead(formData)
      .then(function (payload) {
        var count = Math.max(0, payload.unread || 0);
        lastCount = count;
        updateBadges(count);

        if (listItem) {
          listItem.classList.remove('emsp-notif-item--unread');
          var dot = listItem.querySelector('.emsp-notif-item__dot');
          if (dot) {
            dot.remove();
          }
        }

        closePanel();

        var target = resolveHref(redirectTo || payload.redirect || '');
        if (target) {
          window.location.href = target;
        }
      })
      .catch(function () {
        /* Fallback navigation sans AJAX */
        closePanel();
        var target = resolveHref(redirectTo);
        if (target) {
          window.location.href = target;
        }
      });
  }

  function markAllRead() {
    var formData = new FormData();
    formData.append('notification_id', '0');

    postMarkRead(formData)
      .then(function (payload) {
        var count = Math.max(0, payload.unread || 0);
        lastCount = count;
        updateBadges(count);
        document.querySelectorAll('.emsp-notif-item--unread').forEach(function (item) {
          item.classList.remove('emsp-notif-item--unread');
          var dot = item.querySelector('.emsp-notif-item__dot');
          if (dot) {
            dot.remove();
          }
        });
      })
      .catch(function () {
        /* Ignorer */
      });
  }

  function markReadForm(form) {
    if (form.dataset.emspNotifSubmitting === '1') {
      return;
    }

    form.dataset.emspNotifSubmitting = '1';
    var formData = new FormData(form);

    postMarkRead(formData)
      .then(function (payload) {
        var count = Math.max(0, payload.unread || 0);
        lastCount = count;
        updateBadges(count);

        var notificationId = parseInt(formData.get('notification_id') || '0', 10);
        var redirectTo = String(formData.get('redirect_to') || '').trim();
        var isMarkAll = notificationId <= 0;

        if (isMarkAll) {
          document.querySelectorAll('.emsp-notification-item.is-unread, .emsp-notif-item--unread').forEach(function (item) {
            item.classList.remove('is-unread', 'emsp-notif-item--unread');
          });
          document.querySelectorAll('.emsp-notification-item__message.is-unread').forEach(function (msg) {
            msg.classList.remove('is-unread');
          });
          return;
        }

        var target = resolveHref(redirectTo || payload.redirect || '');
        if (target) {
          window.location.href = target;
          return;
        }

        var wrap = form.closest('.emsp-native-list-item-wrap');
        if (wrap) {
          var btn = wrap.querySelector('.emsp-notification-item');
          if (btn) {
            btn.classList.remove('is-unread');
          }
          var msg = wrap.querySelector('.emsp-notification-item__message');
          if (msg) {
            msg.classList.remove('is-unread');
          }
        }
      })
      .catch(function () {
        form.removeAttribute('data-emsp-notif-submitting');
        form.submit();
      });
  }

  function bindMarkReadForms() {
    document.querySelectorAll('form[action*="notifications/mark-read"]').forEach(function (form) {
      if (form.dataset.emspNotifBound === '1') {
        return;
      }
      form.dataset.emspNotifBound = '1';
      form.addEventListener('submit', function (ev) {
        if (!config.markReadUrl && !form.getAttribute('action')) {
          return;
        }
        ev.preventDefault();
        markReadForm(form);
      });
    });
  }

  function isMobileViewport() {
    return MOBILE_MQ.matches;
  }

  function getBackdrop() {
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'emsp-notif-backdrop';
      backdrop.hidden = true;
      backdrop.setAttribute('aria-hidden', 'true');
      backdrop.addEventListener('click', function () {
        closePanel();
      });
    }
    return backdrop;
  }

  function ensureBackdropInDom() {
    var el = getBackdrop();
    if (!el.parentNode) {
      document.body.appendChild(el);
    }
    return el;
  }

  function detachBackdrop() {
    var el = getBackdrop();
    el.hidden = true;
    el.classList.remove('is-visible');
    if (el.parentNode) {
      el.parentNode.removeChild(el);
    }
  }

  function lockBodyScroll() {
    if (!document.body.classList.contains('emsp-notif-sheet-open')) {
      document.body.classList.add('emsp-notif-sheet-open');
    }
  }

  function unlockBodyScroll() {
    document.body.classList.remove('emsp-notif-sheet-open');
  }

  function forceCleanupOverlayState() {
    clearCloseTimer();
    panelOpen = false;
    resetPanelPresentation();

    var p = getPanel();
    if (p) {
      p.hidden = true;
    }

    detachBackdrop();
    unlockBodyScroll();

    if (activeTrigger) {
      activeTrigger.setAttribute('aria-expanded', 'false');
      activeTrigger = null;
    }

    document.removeEventListener('click', onDocClick, true);
    document.removeEventListener('keydown', onKeyDown);
  }

  function applyPanelMode(p) {
    if (!p) {
      return;
    }

    if (isMobileViewport()) {
      p.classList.add('is-mobile-sheet');
      p.setAttribute('aria-modal', 'true');
    } else {
      p.classList.remove('is-mobile-sheet', 'is-closing');
      p.setAttribute('aria-modal', 'false');
    }
  }

  function clearCloseTimer() {
    if (closeTimerId !== null) {
      window.clearTimeout(closeTimerId);
      closeTimerId = null;
    }
  }

  function finalizeClosePanel() {
    var p = getPanel();
    clearCloseTimer();

    if (!p) {
      panelOpen = false;
      unlockBodyScroll();
      detachBackdrop();
      return;
    }

    p.hidden = true;
    resetPanelPresentation();
    panelOpen = false;

    detachBackdrop();
    unlockBodyScroll();

    if (activeTrigger) {
      activeTrigger.setAttribute('aria-expanded', 'false');
      activeTrigger = null;
    }

    document.removeEventListener('click', onDocClick, true);
    document.removeEventListener('keydown', onKeyDown);
  }

  function positionPanel(trigger) {
    var p = ensurePanelInBody();
    if (!p || !trigger) {
      return;
    }

    applyPanelMode(p);
    p.style.position = 'fixed';

    if (isMobileViewport()) {
      p.style.top = 'auto';
      p.style.left = '0';
      p.style.right = '0';
      p.style.bottom = '0';
      p.style.width = '100%';
      p.style.maxWidth = '100%';
      return;
    }

    var panelWidth = 360;
    var gutter = 8;
    var rect = trigger.getBoundingClientRect();
    var viewportWidth = window.innerWidth || document.documentElement.clientWidth || panelWidth;
    var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 640;
    var right = Math.max(gutter, Math.round(viewportWidth - rect.right));
    var maxRight = Math.max(gutter, viewportWidth - panelWidth - gutter);

    if (right > maxRight) {
      right = maxRight;
    }

    var top = Math.round(rect.bottom + gutter);
    var estimatedHeight = Math.min(520, viewportHeight - top - gutter);

    if (estimatedHeight < 220 && rect.top > panelWidth) {
      top = Math.max(gutter, Math.round(rect.top - estimatedHeight - gutter));
    }

    p.style.top = top + 'px';
    p.style.right = right + 'px';
    p.style.left = 'auto';
    p.style.bottom = 'auto';
    p.style.width = panelWidth + 'px';
    p.style.maxWidth = 'min(360px, calc(100vw - 16px))';
  }

  function closePanel() {
    var p = getPanel();
    if (!p || !panelOpen) {
      return;
    }

    clearCloseTimer();

    if (isMobileViewport() && p.classList.contains('is-mobile-sheet')) {
      p.classList.remove('is-open');
      p.classList.add('is-closing');

      var b = getBackdrop();
      b.classList.remove('is-visible');
      unlockBodyScroll();

      closeTimerId = window.setTimeout(finalizeClosePanel, 320);
      return;
    }

    finalizeClosePanel();
  }

  function openPanel(trigger) {
    var p = ensurePanelInBody();
    if (!p) {
      return;
    }

    clearCloseTimer();
    activeTrigger = trigger;
    positionPanel(trigger);

    p.classList.remove('is-closing');
    p.hidden = false;

    if (isMobileViewport()) {
      var b = ensureBackdropInDom();
      b.hidden = false;
      lockBodyScroll();
      window.requestAnimationFrame(function () {
        b.classList.add('is-visible');
        p.classList.add('is-open');
      });
    } else {
      unlockBodyScroll();
      detachBackdrop();
      window.requestAnimationFrame(function () {
        p.classList.add('is-open');
      });
    }

    panelOpen = true;
    trigger.setAttribute('aria-expanded', 'true');
    fetchRecentList();

    document.addEventListener('click', onDocClick, true);
    document.addEventListener('keydown', onKeyDown);
  }

  function togglePanel(trigger) {
    if (panelOpen && activeTrigger === trigger) {
      closePanel();
      return;
    }
    if (panelOpen) {
      closePanel();
    }
    openPanel(trigger);
  }

  function onDocClick(ev) {
    var p = getPanel();
    if (!p) {
      return;
    }
    if (p.contains(ev.target)) {
      return;
    }
    if (ev.target.closest('[data-emsp-notif-trigger]')) {
      return;
    }
    closePanel();
  }

  function onKeyDown(ev) {
    if (ev.key === 'Escape') {
      closePanel();
    }
  }

  function hideOffcanvasIfOpen() {
    var offcanvas = document.getElementById('emspMainOffcanvas');
    if (!offcanvas || !offcanvas.classList.contains('show')) {
      return;
    }
    if (window.bootstrap && bootstrap.Offcanvas) {
      var instance = bootstrap.Offcanvas.getInstance(offcanvas);
      if (instance) {
        instance.hide();
      }
    }
  }

  function bindTriggers() {
    document.querySelectorAll('[data-emsp-notif-trigger]').forEach(function (trigger) {
      if (trigger.dataset.emspNotifTriggerBound === '1') {
        return;
      }
      if (trigger.classList.contains('emsp-open-login-modal')) {
        return;
      }

      trigger.dataset.emspNotifTriggerBound = '1';

      if (trigger.tagName === 'A' && !trigger.getAttribute('href')) {
        trigger.setAttribute('href', '#');
      }

      trigger.addEventListener('click', function (ev) {
        ev.preventDefault();
        ev.stopPropagation();
        hideOffcanvasIfOpen();
        togglePanel(trigger);
      });
    });
  }

  function bindSwipeToClose(handle) {
    if (!handle || handle.dataset.emspNotifSwipeBound === '1') {
      return;
    }

    handle.dataset.emspNotifSwipeBound = '1';

    var startY = 0;
    var tracking = false;

    handle.addEventListener('touchstart', function (ev) {
      if (!isMobileViewport() || !panelOpen) {
        return;
      }
      if (!ev.touches || !ev.touches.length) {
        return;
      }
      startY = ev.touches[0].clientY;
      tracking = true;
    }, { passive: true });

    handle.addEventListener('touchmove', function (ev) {
      if (!tracking || !ev.touches || !ev.touches.length) {
        return;
      }
      var delta = ev.touches[0].clientY - startY;
      if (delta > 72) {
        tracking = false;
        closePanel();
      }
    }, { passive: true });

    handle.addEventListener('touchend', function () {
      tracking = false;
    }, { passive: true });
  }

  function bindPanelActions() {
    var markAllBtn = queryPanel('[data-emsp-notif-mark-all]');
    if (markAllBtn && markAllBtn.dataset.emspNotifBound !== '1') {
      markAllBtn.dataset.emspNotifBound = '1';
      markAllBtn.addEventListener('click', function (ev) {
        ev.preventDefault();
        markAllRead();
      });
    }

    var closeBtn = queryPanel('[data-emsp-notif-close]');
    if (closeBtn && closeBtn.dataset.emspNotifBound !== '1') {
      closeBtn.dataset.emspNotifBound = '1';
      closeBtn.addEventListener('click', function (ev) {
        ev.preventDefault();
        closePanel();
      });
    }

    bindSwipeToClose(queryPanel('[data-emsp-notif-handle]'));
  }

  function startPolling() {
    stopPolling();
    fetchUnreadCount();
    timerId = window.setInterval(fetchUnreadCount, POLL_MS);
  }

  function stopPolling() {
    if (timerId !== null) {
      window.clearInterval(timerId);
      timerId = null;
    }
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      stopPolling();
      return;
    }
    startPolling();
    if (panelOpen) {
      fetchRecentList();
    }
  });

  window.addEventListener('resize', function () {
    if (!panelOpen) {
      return;
    }

    var p = getPanel();
    if (!p) {
      return;
    }

    applyPanelMode(p);

    if (isMobileViewport()) {
      ensureBackdropInDom();
      getBackdrop().hidden = false;
      getBackdrop().classList.add('is-visible');
      lockBodyScroll();
      positionPanel(activeTrigger || document.querySelector('[data-emsp-notif-trigger]:not(.emsp-open-login-modal)'));
      return;
    }

    unlockBodyScroll();
    detachBackdrop();
    if (activeTrigger) {
      positionPanel(activeTrigger);
    }
  });

  function bindGlobalDismissHandlers() {
    document.addEventListener('show.bs.modal', function () {
      closePanel();
    });

    document.addEventListener('show.bs.offcanvas', function () {
      closePanel();
    });

    window.addEventListener('pagehide', forceCleanupOverlayState);
    document.addEventListener('visibilitychange', function () {
      if (document.hidden && panelOpen) {
        forceCleanupOverlayState();
      }
    });
  }

  function init() {
    ensurePanelInBody();
    forceCleanupOverlayState();
    bindMarkReadForms();
    bindTriggers();
    bindPanelActions();
    bindGlobalDismissHandlers();
    startPolling();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
