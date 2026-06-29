/* MiniOrange PDProtect - Confirm Action page JS (frontend)
 *
 * Reads config from window.mopdpConfirmConfig set by the phtml template:
 *   window.mopdpConfirmConfig = {
 *       action, actionUrl, privacyUrl, downloadServeUrl,
 *       msgPassword, msgUnexpected, msgError, msgDownloadReady, msgSuccess
 *   };
 */

(function () {
    var cfg              = window.mopdpConfirmConfig || {};
    var ACTION            = cfg.action || '';
    var ACTION_URL        = cfg.actionUrl || '';
    var PRIVACY_URL       = cfg.privacyUrl || '';
    var DOWNLOAD_SERVE_URL = cfg.downloadServeUrl || '';

    window.mopdpConfirmAction = function () {
        var password = '';
        var pwdField = document.getElementById('mopdp-confirm-password');
        if (pwdField) {
            password = pwdField.value;
            if (!password) {
                showError(cfg.msgPassword || 'Please enter your password.');
                return;
            }
        }

        var btn = document.getElementById('mopdp-confirm-btn');
        btn.disabled = true;

        hideError();

        var xhr = new XMLHttpRequest();
        xhr.open('POST', ACTION_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) { return; }
            btn.disabled = false;

            var resp;
            try {
                resp = JSON.parse(xhr.responseText); 
            }
            catch (e) {
                showError(cfg.msgUnexpected || 'An unexpected error occurred.');
                return;
            }

            if (!resp.success) {
                showError(resp.message || cfg.msgError || 'An error occurred. Please try again.');
                return;
            }

            if (ACTION === 'download') {
                /* Password validated — show success, then fetch the file via a
                 * hidden iframe. The server returns Content-Disposition:attachment
                 * so the browser saves the file without navigating away. */
                showSuccess(cfg.msgDownloadReady || 'Your data is ready. The file download will begin shortly.');
                btn.disabled = true;
                var iframe = document.createElement('iframe');
                iframe.style.cssText = 'display:none;width:0;height:0;border:0;';
                iframe.src = DOWNLOAD_SERVE_URL;
                document.body.appendChild(iframe);
                /* Clean up the iframe after the browser has had time to receive the file. */
                setTimeout(function () {
                    if (iframe.parentNode) { iframe.parentNode.removeChild(iframe); }
                }, 15000);
            } else {
                if (ACTION === 'withdraw') {
                    document.cookie = 'mopdp_consent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; SameSite=Lax';
                }
                showSuccess(resp.message || cfg.msgSuccess || 'Action completed successfully.');
                btn.disabled = true;
                setTimeout(function () { window.location.href = PRIVACY_URL; }, 2000);
            }
        };

        xhr.send(password ? 'password=' + encodeURIComponent(password) : 'consent=withdrawn');
    };

    function showError(msg) {
        var el = document.getElementById('mopdp-confirm-error');
        el.textContent   = msg;
        el.style.display = 'block';
        document.getElementById('mopdp-confirm-success').style.display = 'none';
    }

    function hideError() {
        document.getElementById('mopdp-confirm-error').style.display  = 'none';
        document.getElementById('mopdp-confirm-success').style.display = 'none';
    }

    function showSuccess(msg) {
        var el = document.getElementById('mopdp-confirm-success');
        el.textContent   = msg;
        el.style.display = 'block';
        document.getElementById('mopdp-confirm-error').style.display = 'none';
    }

    /* Allow pressing Enter in the password field to submit */
    document.addEventListener('DOMContentLoaded', function () {
        var pwdField = document.getElementById('mopdp-confirm-password');
        if (pwdField) {
            pwdField.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { mopdpConfirmAction(); }
            });
        }
    });
}());
