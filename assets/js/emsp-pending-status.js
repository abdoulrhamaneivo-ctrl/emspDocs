(function () {
  'use strict';

  var root = document.querySelector('[data-emsp-pending-status]');
  if (!root) {
    return;
  }

  var pollUrl = root.getAttribute('data-poll-url') || window.location.pathname;
  var intervalMs = 30000;
  var pollTimer = null;

  function reloadPage() {
    window.location.reload();
  }

  root.querySelectorAll('.emsp-pending-refresh-btn').forEach(function (btn) {
    btn.addEventListener('click', reloadPage);
  });

  var cooldown = parseInt(root.getAttribute('data-cooldown') || '0', 10);
  var resendBtn = root.querySelector('.emsp-pending-resend-btn');
  var resendLabel = root.querySelector('[data-resend-label]');
  var resendFormId = resendBtn ? resendBtn.getAttribute('data-resend-form') : null;
  var cooldownTimer = null;

  function formatCooldown(seconds) {
    if (seconds >= 60) {
      var mins = Math.ceil(seconds / 60);
      return 'Renvoyer dans ' + mins + ' min';
    }
    return 'Renvoyer dans ' + seconds + ' s';
  }

  function tickCooldown() {
    if (cooldown <= 0) {
      if (cooldownTimer) {
        window.clearInterval(cooldownTimer);
      }
      reloadPage();
      return;
    }
    if (resendLabel) {
      resendLabel.textContent = formatCooldown(cooldown);
    }
    cooldown -= 1;
  }

  if (cooldown > 0 && resendBtn) {
    tickCooldown();
    cooldownTimer = window.setInterval(tickCooldown, 1000);
  } else if (resendBtn && resendFormId) {
    resendBtn.addEventListener('click', function () {
      var form = document.getElementById(resendFormId);
      if (form) {
        form.submit();
      }
    });
  }

  if (root.getAttribute('data-poll') !== '1') {
    return;
  }

  function shouldReload() {
    if (document.hidden) {
      return;
    }
    fetch(pollUrl, {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (response) {
        if (!response.ok) {
          return null;
        }
        return response.text();
      })
      .then(function (html) {
        if (!html) {
          return;
        }
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var nextHero = doc.querySelector('.emsp-pending-surface__hero-title');
        var currentHero = root.querySelector('.emsp-pending-surface__hero-title');
        if (!nextHero || !currentHero) {
          return;
        }
        if (nextHero.textContent.trim() !== currentHero.textContent.trim()) {
          reloadPage();
        }
      })
      .catch(function () {
        /* silent — polling is best-effort */
      });
  }

  pollTimer = window.setInterval(shouldReload, intervalMs);
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      shouldReload();
    }
  });

  window.addEventListener('pagehide', function () {
    if (pollTimer) {
      window.clearInterval(pollTimer);
    }
    if (cooldownTimer) {
      window.clearInterval(cooldownTimer);
    }
  });
})();
