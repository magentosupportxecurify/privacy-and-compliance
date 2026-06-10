define(['jquery'], function ($) {
    'use strict';

    function buildPrefs(allOn) {
        return {
            necessary: true,
            analytics: allOn,
            functional: allOn,
            marketing: allOn
        };
    }

    function buildNecessaryOnlyPrefs() {
        return {
            necessary: true,
            analytics: false,
            functional: false,
            marketing: false
        };
    }

    return function (config) {
        var storageKey = config.storageKey || 'mo_consent_preferences';

        try {
            if (window.localStorage.getItem(storageKey)) {
                return;
            }
        } catch (e) {
            return;
        }

        var $banner = $('#mo-cookie-banner');
        if (!$banner.length) {
            return;
        }

        var $modal = $('#mo-cookie-customize-modal');

        function applyAndClose(prefs) {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify(prefs));
            } catch (e) {
                // ignore
            }

            if (window.moConsentUnblock) {
                window.moConsentUnblock(prefs);
            }

            $.ajax({
                url: config.saveUrl,
                type: 'POST',
                data: JSON.stringify({ preferences: prefs }),
                contentType: 'application/json',
                dataType: 'json'
            }).fail(function () {
                // non-blocking
            });

            $modal.hide();
            $banner.removeClass('is-visible');
            setTimeout(function () {
                $banner.hide();
            }, 400);
        }

        $banner.on('click', '[data-action]', function () {
            var action = $(this).data('action');
            if (action === 'accept-all') {
                applyAndClose(buildPrefs(true));
            } else if (action === 'reject-all') {
                applyAndClose(buildNecessaryOnlyPrefs());
            } else if (action === 'customize') {
                $modal.show();
            }
        });

        $modal.on('click', function (e) {
            var $target = $(e.target);
            if ($target.hasClass('vcc-modal__backdrop') || $target.hasClass('vcc-modal__close') ||
                $target.closest('.vcc-modal__close').length) {
                $modal.hide();
                return;
            }
            var modalAction = $target.closest('[data-modal-action]').data('modal-action');
            if (modalAction === 'cancel') {
                $modal.hide();
            } else if (modalAction === 'save') {
                var prefs = { necessary: true, analytics: false, functional: false, marketing: false };
                $modal.find('[data-category]').each(function () {
                    prefs[$(this).data('category')] = $(this).prop('checked');
                });
                applyAndClose(prefs);
            }
        });
    };
});
