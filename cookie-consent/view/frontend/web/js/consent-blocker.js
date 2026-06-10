(function () {
    'use strict';

    var STORAGE_KEY = 'mo_consent_preferences';
    var stored = null;

    try {
        stored = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
    } catch (e) {
        stored = null;
    }

    var _nativeCookie = Object.getOwnPropertyDescriptor(Document.prototype, 'cookie');
    if (_nativeCookie && _nativeCookie.set) {
        Object.defineProperty(document, 'cookie', {
            get: function () {
                return _nativeCookie.get.call(document);
            },
            set: function (val) {
                var alwaysAllow = /^(PHPSESSID|form_key|store|mage-|X-Magento|private_content_version|section_data_ids)/i;
                if (!stored && !alwaysAllow.test(val)) {
                    window.__vendor_blocked_cookies = window.__vendor_blocked_cookies || [];
                    window.__vendor_blocked_cookies.push(val);
                    return;
                }
                _nativeCookie.set.call(document, val);
            },
            configurable: true
        });
    }

    window.moConsentUnblock = function (prefs) {
        document.querySelectorAll('script[type="text/plain"][data-consent-category]').forEach(function (el) {
            var cat = el.getAttribute('data-consent-category');
            if (!prefs[cat]) {
                return;
            }
            var s = document.createElement('script');
            Array.from(el.attributes).forEach(function (attr) {
                if (attr.name === 'type') {
                    return;
                }
                if (attr.name === 'data-src') {
                    s.src = attr.value;
                } else if (attr.name !== 'data-consent-category') {
                    s.setAttribute(attr.name, attr.value);
                }
            });
            if (el.textContent.trim()) {
                s.textContent = el.textContent;
            }
            el.parentNode.replaceChild(s, el);
        });

        (window.__vendor_blocked_cookies || []).forEach(function (c) {
            _nativeCookie.set.call(document, c);
        });
        window.__vendor_blocked_cookies = [];

        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        } catch (e) {
            // ignore
        }
    };

    if (stored) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                window.moConsentUnblock(stored);
            });
        } else {
            window.moConsentUnblock(stored);
        }
    }
})();
