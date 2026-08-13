(function (window, document) {
    'use strict';

    var SCROLL_THRESHOLD = 8;
    var scrollTicking = false;

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }
        callback();
    }

    function initNavbarScrollState() {
        var header = document.querySelector('.emsp-app-header');
        if (!header) {
            return;
        }

        function updateScrollState() {
            var scrolled = window.scrollY > SCROLL_THRESHOLD;
            header.classList.toggle('emsp-header-scrolled', scrolled);
            header.classList.toggle('is-scrolled', scrolled);
            scrollTicking = false;
        }

        function onScroll() {
            if (scrollTicking) {
                return;
            }
            scrollTicking = true;
            window.requestAnimationFrame(updateScrollState);
        }

        updateScrollState();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    function initNotificationPulse() {
        if (prefersReducedMotion()) {
            return;
        }

        document.querySelectorAll('.emsp-navbar-notifications').forEach(function (trigger) {
            if (trigger.querySelector('.emsp-navbar-notifications__badge')) {
                trigger.classList.add('has-unread');
            }
        });
    }

    onReady(function () {
        initNavbarScrollState();
        initNotificationPulse();
    });
})(window, document);
