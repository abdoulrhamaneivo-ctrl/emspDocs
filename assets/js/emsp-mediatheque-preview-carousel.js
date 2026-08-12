(function (window, document) {
    'use strict';

    var MOBILE_MQ = window.matchMedia('(max-width: 767.98px)');
    var AUTOPLAY_MS = 4000;

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

    function initCarousel(root) {
        if (!MOBILE_MQ.matches) {
            return;
        }

        var track = root.querySelector('[data-emsp-carousel-track]');
        var slides = track ? track.querySelectorAll('[data-emsp-carousel-slide]') : [];
        var dots = root.querySelectorAll('[data-emsp-carousel-dot]');

        if (!track || slides.length <= 1) {
            return;
        }

        var index = 0;
        var timer = null;
        var touchStartX = 0;
        var touchDeltaX = 0;
        var isPaused = false;

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

        function stopAutoplay() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function startAutoplay() {
            stopAutoplay();
            if (prefersReducedMotion() || isPaused || slides.length <= 1) {
                return;
            }
            timer = window.setInterval(function () {
                goTo(index + 1);
            }, AUTOPLAY_MS);
        }

        function pauseAutoplay() {
            isPaused = true;
            stopAutoplay();
        }

        function resumeAutoplay() {
            isPaused = false;
            startAutoplay();
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var target = parseInt(dot.getAttribute('data-emsp-carousel-dot') || '0', 10);
                goTo(target);
                pauseAutoplay();
                window.setTimeout(resumeAutoplay, AUTOPLAY_MS * 2);
            });
        });

        root.addEventListener('touchstart', function (event) {
            if (!event.touches || !event.touches.length) {
                return;
            }
            touchStartX = event.touches[0].clientX;
            touchDeltaX = 0;
            pauseAutoplay();
        }, { passive: true });

        root.addEventListener('touchmove', function (event) {
            if (!event.touches || !event.touches.length) {
                return;
            }
            touchDeltaX = event.touches[0].clientX - touchStartX;
        }, { passive: true });

        root.addEventListener('touchend', function () {
            if (Math.abs(touchDeltaX) > 42) {
                goTo(touchDeltaX < 0 ? index + 1 : index - 1);
            }
            window.setTimeout(resumeAutoplay, AUTOPLAY_MS);
        });

        root.addEventListener('mouseenter', pauseAutoplay);
        root.addEventListener('mouseleave', resumeAutoplay);

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) {
                pauseAutoplay();
            } else {
                resumeAutoplay();
            }
        });

        MOBILE_MQ.addEventListener('change', function () {
            if (!MOBILE_MQ.matches) {
                stopAutoplay();
                track.style.transform = '';
            } else {
                goTo(index);
                resumeAutoplay();
            }
        });

        goTo(0);
        startAutoplay();
    }

    onReady(function () {
        document.querySelectorAll('[data-emsp-mediatheque-carousel]').forEach(initCarousel);
    });
}(window, document));
