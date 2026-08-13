(function (document, window) {
  'use strict';

  var AUTOPLAY_MS = 4500;
  var DESKTOP_MQ = window.matchMedia('(min-width: 992px)');
  var resizeObserver = null;
  var matchScheduled = false;

  function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function isElementVisible(el) {
    if (!el || !el.isConnected) return false;
    if (el.offsetParent === null && el.getClientRects().length === 0) {
      var style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden') {
        return false;
      }
    }
    var rect = el.getBoundingClientRect();
    return rect.width > 0 && rect.height > 0;
  }

  function clearInlineHeights(nodes) {
    nodes.forEach(function (node) {
      if (!node) return;
      node.style.height = '';
      node.style.maxHeight = '';
      node.style.minHeight = '';
      node.style.marginTop = '';
    });
  }

  function matchCarouselToForm() {
    var targets = document.querySelectorAll('.emsp-auth-register-aside, .emsp-auth-login-aside');

    if (!DESKTOP_MQ.matches) {
      targets.forEach(function (aside) {
        clearInlineHeights([aside]);
        var carousel = aside.querySelector('.emsp-auth-aside-carousel:not(.emsp-auth-aside-carousel--compact)');
        clearInlineHeights([carousel, carousel && carousel.querySelector('.emsp-auth-aside-carousel__viewport')]);
      });
      scheduleCarouselInit();
      return;
    }

    document.querySelectorAll('.emsp-auth-register-layout, .emsp-auth-login-layout').forEach(function (layout) {
      layout.style.alignItems = 'start';

      var aside = layout.querySelector('.emsp-auth-register-aside, .emsp-auth-login-aside');
      var formSurface = layout.querySelector('.emsp-register-form-unified, .emsp-login-surface');
      if (!aside || !formSurface) return;

      aside.style.position = 'relative';
      aside.style.top = 'auto';
      aside.style.paddingTop = '0';
      aside.style.marginTop = '0';
      aside.style.alignSelf = 'start';
      aside.style.display = 'flex';
      aside.style.flexDirection = 'column';
      aside.style.justifyContent = 'flex-start';
      aside.style.overflow = 'hidden';

      var formRect = formSurface.getBoundingClientRect();
      var formHeight = Math.ceil(formRect.height);
      if (formHeight <= 0) return;

      var asideRect = aside.getBoundingClientRect();
      var topDelta = Math.round(formRect.top - asideRect.top);
      if (Math.abs(topDelta) > 1) {
        aside.style.marginTop = topDelta + 'px';
      }

      aside.style.height = formHeight + 'px';
      aside.style.maxHeight = formHeight + 'px';
      aside.style.minHeight = '0';

      var carousel = aside.querySelector('.emsp-auth-aside-carousel:not(.emsp-auth-aside-carousel--compact)');
      if (!carousel) return;

      carousel.style.flex = '1 1 auto';
      carousel.style.alignSelf = 'stretch';
      carousel.style.height = '100%';
      carousel.style.maxHeight = '100%';
      carousel.style.minHeight = '0';
      carousel.style.marginTop = '0';

      var viewport = carousel.querySelector('.emsp-auth-aside-carousel__viewport');
      if (viewport) {
        viewport.style.flex = '1 1 auto';
        viewport.style.height = '100%';
        viewport.style.maxHeight = '100%';
        viewport.style.minHeight = '0';
        viewport.style.aspectRatio = 'auto';
      }
    });

    scheduleCarouselInit();
  }

  function scheduleCarouselInit() {
    window.requestAnimationFrame(function () {
      document.querySelectorAll('[data-emsp-auth-aside-carousel]').forEach(function (root) {
        if (root.dataset.emspAuthAsideReady !== '1') {
          initCarousel(root);
        } else if (typeof root._emspCarouselStart === 'function') {
          root._emspCarouselStart();
        }
      });
    });
  }

  function scheduleMatchCarouselToForm() {
    if (matchScheduled) return;
    matchScheduled = true;
    window.requestAnimationFrame(function () {
      matchScheduled = false;
      matchCarouselToForm();
    });
  }

  function syncViewportHeight(root) {
    if (root.classList.contains('emsp-auth-aside-carousel--compact')) {
      var compactViewport = root.querySelector('.emsp-auth-aside-carousel__viewport');
      if (compactViewport) {
        compactViewport.style.height = '';
        compactViewport.style.maxHeight = '';
        compactViewport.style.minHeight = '';
      }
      return;
    }
    scheduleMatchCarouselToForm();
  }

  function observeFormSurfaces() {
    if (typeof window.ResizeObserver !== 'function') return;

    if (resizeObserver) {
      resizeObserver.disconnect();
    }

    resizeObserver = new ResizeObserver(function () {
      scheduleMatchCarouselToForm();
    });

    document.querySelectorAll('.emsp-register-form-unified, .emsp-login-surface').forEach(function (surface) {
      resizeObserver.observe(surface);
    });

    var wizard = document.querySelector('[data-emsp-register-wizard]');
    if (wizard && typeof window.MutationObserver === 'function') {
      var wizardObserver = new MutationObserver(function () {
        scheduleMatchCarouselToForm();
      });
      wizardObserver.observe(wizard, {
        attributes: true,
        attributeFilter: ['class', 'hidden'],
        subtree: true,
        childList: false
      });
    }
  }

  function initCarousel(root) {
    if (root.dataset.emspAuthAsideReady === '1') {
      syncViewportHeight(root);
      if (typeof root._emspCarouselStart === 'function') {
        root._emspCarouselStart();
      }
      return;
    }

    root.dataset.emspAuthAsideReady = '1';
    syncViewportHeight(root);

    var track = root.querySelector('[data-emsp-auth-aside-track]');
    var slides = track ? track.querySelectorAll('[data-emsp-auth-aside-slide]') : [];
    var dots = root.querySelectorAll('[data-emsp-auth-aside-dot]');

    if (!track || slides.length <= 1) {
      return;
    }

    if (prefersReducedMotion()) {
      slides.forEach(function (slide, index) {
        var active = index === 0;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });
      dots.forEach(function (dot, index) {
        var active = index === 0;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      track.style.transform = 'translate3d(0, 0, 0)';
      return;
    }

    var index = 0;
    var timer = null;
    var hoverPaused = false;
    var focusPaused = false;

    function paused() {
      return hoverPaused || focusPaused || document.hidden;
    }

    function goTo(nextIndex) {
      var total = slides.length;
      index = ((nextIndex % total) + total) % total;

      slides.forEach(function (slide, slideIndex) {
        var active = slideIndex === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', active ? 'false' : 'true');
      });

      dots.forEach(function (dot, dotIndex) {
        var active = dotIndex === index;
        dot.classList.toggle('is-active', active);
        dot.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      track.style.transform = 'translate3d(-' + (index * 100) + '%, 0, 0)';
    }

    function startAutoplay() {
      stopAutoplay();
      if (paused()) return;
      timer = window.setInterval(function () {
        goTo(index + 1);
      }, AUTOPLAY_MS);
    }

    function stopAutoplay() {
      if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
      }
    }

    root._emspCarouselStart = startAutoplay;
    root._emspCarouselStop = stopAutoplay;

    dots.forEach(function (dot, dotIndex) {
      dot.addEventListener('click', function () {
        goTo(dotIndex);
        startAutoplay();
      });
    });

    root.addEventListener('mouseenter', function () {
      hoverPaused = true;
      stopAutoplay();
    });

    root.addEventListener('mouseleave', function () {
      hoverPaused = false;
      startAutoplay();
    });

    root.addEventListener('focusin', function () {
      focusPaused = true;
      stopAutoplay();
    });

    root.addEventListener('focusout', function (event) {
      if (root.contains(event.relatedTarget)) {
        return;
      }
      focusPaused = false;
      startAutoplay();
    });

    document.addEventListener('visibilitychange', function onVisibilityChange() {
      if (document.hidden) {
        stopAutoplay();
      } else {
        startAutoplay();
      }
    });

    goTo(0);
    startAutoplay();
  }

  function bootWhenVisible() {
    scheduleMatchCarouselToForm();
    document.querySelectorAll('[data-emsp-auth-aside-carousel]').forEach(function (root) {
      syncViewportHeight(root);
      initCarousel(root);
    });
  }

  function boot() {
    observeFormSurfaces();
    bootWhenVisible();
    window.requestAnimationFrame(function () {
      bootWhenVisible();
      window.setTimeout(bootWhenVisible, 120);
      window.setTimeout(bootWhenVisible, 420);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.addEventListener('resize', bootWhenVisible, { passive: true });
  document.addEventListener('visibilitychange', bootWhenVisible);

  if (typeof DESKTOP_MQ.addEventListener === 'function') {
    DESKTOP_MQ.addEventListener('change', bootWhenVisible);
  } else if (typeof DESKTOP_MQ.addListener === 'function') {
    DESKTOP_MQ.addListener(bootWhenVisible);
  }

  if (typeof window.IntersectionObserver === 'function') {
    var pendingRoots = document.querySelectorAll('[data-emsp-auth-aside-carousel]');
    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting || entry.intersectionRatio <= 0) {
            if (entry.target._emspCarouselStop) {
              entry.target._emspCarouselStop();
            }
            return;
          }
          initCarousel(entry.target);
        });
      },
      { threshold: [0, 0.12] }
    );
    pendingRoots.forEach(function (root) {
      observer.observe(root);
    });
  }
}(document, window));
