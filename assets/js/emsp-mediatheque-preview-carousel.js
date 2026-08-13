(function (window, document) {
    'use strict';

    var MOBILE_MQ = window.matchMedia('(max-width: 767.98px)');
    var AUTOPLAY_MS = 4500;
    var PAUSE_AFTER_INTERACTION_MS = AUTOPLAY_MS * 2;
    var SWIPE_THRESHOLD_PX = 42;

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

    function bindMediaQueryChange(mq, handler) {
        if (typeof mq.addEventListener === 'function') {
            mq.addEventListener('change', handler);
            return;
        }
        if (typeof mq.addListener === 'function') {
            mq.addListener(handler);
        }
    }

    function initCarousel(root) {
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
        var userPausedUntil = 0;
        var isInView = true;
        var isPageVisible = !document.hidden;
        var resumeTimer = null;

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

        function canAutoplay() {
            return MOBILE_MQ.matches
                && !prefersReducedMotion()
                && slides.length > 1
                && isInView
                && isPageVisible
                && Date.now() >= userPausedUntil;
        }

        function stopAutoplay() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function clearResumeTimer() {
            if (resumeTimer !== null) {
                window.clearTimeout(resumeTimer);
                resumeTimer = null;
            }
        }

        function startAutoplay() {
            stopAutoplay();
            if (!canAutoplay()) {
                return;
            }
            timer = window.setInterval(function () {
                if (!canAutoplay()) {
                    stopAutoplay();
                    return;
                }
                goTo(index + 1);
            }, AUTOPLAY_MS);
        }

        function scheduleResume(delayMs) {
            clearResumeTimer();
            resumeTimer = window.setTimeout(function () {
                resumeTimer = null;
                if (Date.now() >= userPausedUntil) {
                    startAutoplay();
                }
            }, delayMs);
        }

        function pauseFor(ms) {
            userPausedUntil = Date.now() + ms;
            stopAutoplay();
            scheduleResume(ms + 50);
        }

        function pauseBriefly() {
            pauseFor(PAUSE_AFTER_INTERACTION_MS);
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var target = parseInt(dot.getAttribute('data-emsp-carousel-dot') || '0', 10);
                goTo(target);
                pauseBriefly();
            });
        });

        root.addEventListener('touchstart', function (event) {
            if (!event.touches || !event.touches.length) {
                return;
            }
            touchStartX = event.touches[0].clientX;
            touchDeltaX = 0;
            userPausedUntil = Date.now() + PAUSE_AFTER_INTERACTION_MS;
            stopAutoplay();
        }, { passive: true });

        root.addEventListener('touchmove', function (event) {
            if (!event.touches || !event.touches.length) {
                return;
            }
            touchDeltaX = event.touches[0].clientX - touchStartX;
        }, { passive: true });

        function finishTouchInteraction() {
            if (Math.abs(touchDeltaX) > SWIPE_THRESHOLD_PX) {
                goTo(touchDeltaX < 0 ? index + 1 : index - 1);
            }
            touchDeltaX = 0;
            pauseFor(AUTOPLAY_MS);
        }

        root.addEventListener('touchend', finishTouchInteraction, { passive: true });
        root.addEventListener('touchcancel', finishTouchInteraction, { passive: true });

        root.addEventListener('mouseenter', function () {
            userPausedUntil = Date.now() + PAUSE_AFTER_INTERACTION_MS;
            stopAutoplay();
        });
        root.addEventListener('mouseleave', function () {
            userPausedUntil = 0;
            startAutoplay();
        });

        document.addEventListener('visibilitychange', function () {
            isPageVisible = !document.hidden;
            if (isPageVisible) {
                startAutoplay();
            } else {
                stopAutoplay();
            }
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                isInView = entries.some(function (entry) {
                    return entry.isIntersecting;
                });
                if (isInView) {
                    startAutoplay();
                } else {
                    stopAutoplay();
                }
            }, { threshold: 0.25, rootMargin: '0px 0px -5% 0px' });
            observer.observe(root);
        }

        bindMediaQueryChange(MOBILE_MQ, function () {
            if (!MOBILE_MQ.matches) {
                stopAutoplay();
                clearResumeTimer();
                track.style.transform = '';
                slides.forEach(function (slide, slideIndex) {
                    var active = slideIndex === index;
                    slide.classList.toggle('is-active', active);
                    slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                });
            } else {
                goTo(index);
                startAutoplay();
            }
        });

        goTo(0);
        startAutoplay();
    }

    onReady(function () {
        document.querySelectorAll('[data-emsp-mediatheque-carousel]').forEach(initCarousel);
    });
}(window, document));
