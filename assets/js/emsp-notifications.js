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

  function updateBadges(count) {
    var text = formatBadge(count);
    var triggers = document.querySelectorAll('[data-emsp-notif-trigger]');

    triggers.forEach(function (trigger) {
      var badge = trigger.querySelector('.emsp-navbar-notifications__badge');
      var baseLabel = trigger.getAttribute('data-emsp-notif-label') || 'Notifications';

      if (count > 0) {
        if (!badge) {
          badge = document.createElement('span');
          badge.className = 'emsp-navbar-notifications__badge';
          trigger.appendChild(badge);
        }
        badge.textContent = text;
        badge.hidden = false;
      } else if (badge) {
        badge.remove();
      }

      trigger.setAttribute('aria-label', baseLabel + ariaSuffix(count));
      trigger.setAttribute('title', baseLabel + ariaSuffix(count));
    });

    var headerBadge = document.querySelector('.emsp-notifications-header .emsp-native-screen-header__badge');
    if (headerBadge) {
      if (count > 0) {
        headerBadge.textContent = text;
        headerBadge.setAttribute('aria-label', count + ' non lues');
        headerBadge.hidden = false;
      } else {
        headerBadge.hidden = true;
      }
    }

    var markAllForm = document.querySelector('.emsp-notifications-header form[action*="notifications/mark-read"]');
    if (markAllForm) {
      markAllForm.hidden = count <= 0;
    }

    var dashCount = document.querySelector('[data-emsp-notif-dashboard-count]');
    if (dashCount) {
      dashCount.textContent = String(count);
    }

    document.dispatchEvent(new CustomEvent('emsp:notifications:count', {
      detail: { count: count }
    }));
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
        /* Non bloquant : la prochaine visibilité ou intervalle réessaiera. */
      })
      .finally(function () {
        inflight = false;
      });
  }

  function markReadForm(form) {
    if (form.dataset.emspNotifSubmitting === '1') {
      return;
    }

    form.dataset.emspNotifSubmitting = '1';
    var formData = new FormData(form);
    if (config.csrfToken && !formData.get('csrf_token')) {
      formData.append('csrf_token', config.csrfToken);
    }

    var notificationId = parseInt(formData.get('notification_id') || '0', 10);
    var redirectTo = String(formData.get('redirect_to') || '').trim();
    var isMarkAll = notificationId <= 0;

    fetch(config.markReadUrl || form.getAttribute('action'), {
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

        var count = Math.max(0, payload.unread || 0);
        lastCount = count;
        updateBadges(count);

        if (isMarkAll) {
          document.querySelectorAll('.emsp-notification-item.is-unread').forEach(function (item) {
            item.classList.remove('is-unread');
          });
          document.querySelectorAll('.emsp-notification-item__message.is-unread').forEach(function (msg) {
            msg.classList.remove('is-unread');
          });
          return;
        }

        var target = redirectTo || payload.redirect || '';
        if (target) {
          var base = config.appBase || './';
          window.location.href = /^https?:\/\//i.test(target) || target.charAt(0) === '/'
            ? target
            : base + target.replace(/^\//, '');
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
  });

  function init() {
    bindMarkReadForms();
    startPolling();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
