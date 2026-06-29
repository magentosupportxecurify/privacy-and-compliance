(function () {
    'use strict';

    var cfg = window.mopdpConfig || {};
    var COOKIE_NAME = cfg.consentCookie || 'mopdp_consent';
    var DAYS = cfg.cookieDuration || 365;
    var SAVE_URL = cfg.saveUrl || '';

    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setCookie(name, value, days) {
        var expires = new Date(Date.now() + days * 864e5).toUTCString();
        document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function sendConsent(status) {
        if (!SAVE_URL) return;
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SAVE_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send('consent=' + encodeURIComponent(status));
    }

    function closePopup() {
        var popup = document.getElementById('mopdp-privacy-popup');
        if (popup) {
            popup.style.opacity = '0';
            popup.style.transition = 'opacity 0.25s';
            setTimeout(function () { popup.style.display = 'none'; }, 260);
        }
    }

    function init() {
        if (getCookie(COOKIE_NAME)) {
            return;
        }

        var popup = document.getElementById('mopdp-privacy-popup');
        var accept = document.getElementById('mopdp-accept-btn');
        var closeBtn = document.getElementById('mopdp-close-btn');
        var overlay = document.getElementById('mopdp-popup-overlay');

        if (!popup) return;

        popup.style.display = '';

        if (accept) {
            accept.addEventListener('click', function () {
                setCookie(COOKIE_NAME, 'accepted', DAYS);
                sendConsent('accepted');
                closePopup();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                closePopup();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function () {
                closePopup();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
