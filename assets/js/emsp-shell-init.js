(function () {
    'use strict';

    function isVisible(el) {
        return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
    }

    function detectHostBannerOffset() {
        var selectors = [
            '#adcenter', '#ads_banner', '#banner_ad', '#ads',
            '.profreehost-ad', '.host-ad', '.ad-top', '.ad-banner',
            'body > div:first-child > iframe',
            'body > iframe:first-child',
            'body > center:first-child',
            'body > table:first-child'
        ];
        var bestH = 0;
        var bestEl = null;

        selectors.forEach(function (sel) {
            var el = null;
            try { el = document.querySelector(sel); } catch (error) { el = null; }
            if (!isVisible(el)) return;
            var h = 0;
            try { h = Math.round(el.getBoundingClientRect().height || 0); } catch (error2) { h = 0; }
            if (h > bestH) {
                bestH = h;
                bestEl = el;
            }
        });

        if (!bestEl || bestH <= 10) {
            document.documentElement.classList.remove('emsp-host-banner-fixed');
            document.documentElement.style.setProperty('--profreehost-banner-height', '0px');
            return;
        }

        var pos = '';
        var top = 9999;
        try {
            var st = window.getComputedStyle(bestEl);
            pos = st.position || '';
            top = bestEl.getBoundingClientRect().top;
        } catch (error3) {}

        if ((pos === 'fixed' || pos === 'sticky') && top <= 1) {
            document.documentElement.classList.add('emsp-host-banner-fixed');
            document.documentElement.style.setProperty('--profreehost-banner-height', bestH + 'px');
            return;
        }

        document.documentElement.classList.remove('emsp-host-banner-fixed');
        document.documentElement.style.setProperty('--profreehost-banner-height', '0px');
    }

    function initProgressBar() {
        var bar = document.getElementById('emsp-progress-bar');
        if (!bar || bar.dataset.emspProgressInit === '1') return;
        bar.dataset.emspProgressInit = '1';

        var width = 0;
        var timer = setInterval(function () {
            width += Math.random() * 15;
            if (width > 85) {
                clearInterval(timer);
                width = 85;
            }
            bar.style.width = width + '%';
        }, 100);

        window.addEventListener('load', function () {
            clearInterval(timer);
            bar.style.width = '100%';
            setTimeout(function () {
                bar.style.opacity = '0';
                setTimeout(function () {
                    bar.style.width = '0%';
                    bar.style.opacity = '1';
                }, 300);
            }, 300);
        }, { once: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', detectHostBannerOffset);
        document.addEventListener('DOMContentLoaded', initProgressBar);
    } else {
        detectHostBannerOffset();
        initProgressBar();
    }

    window.addEventListener('load', detectHostBannerOffset);
})();
