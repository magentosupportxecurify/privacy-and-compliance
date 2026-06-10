/**
 * MiniOrange PDProtect — Floating Support Widget
 *
 * Reads window.MOPDP_Support.submitUrl (set inline by Support/index.phtml).
 * All other logic lives here so the template stays free of script blocks.
 */
(function () {
    'use strict';

    // ── DOM refs (populated in DOMContentLoaded) ──────────────
    var icon, form, closeBtn, emailInput, phoneInput, queryInput,
        errorBox, formBody, successMsg, submitBtn, tooltip;

    // ── Helpers ──────────────────────────────────────────────

    function showError(msg) {
        if (!errorBox) return;
        errorBox.textContent = msg;
        errorBox.style.display = 'block';
    }

    function clearError() {
        if (!errorBox) return;
        errorBox.style.display = 'none';
        errorBox.textContent = '';
    }

    function openForm() {
        if (!form) return;
        form.classList.add('mopdp-form-open');
        hideTooltip();
    }

    function closeForm() {
        if (!form) return;
        form.classList.remove('mopdp-form-open');
    }

    function showTooltip() {
        if (!tooltip) return;
        tooltip.classList.add('mopdp-tooltip-visible');
    }

    function hideTooltip() {
        if (!tooltip) return;
        tooltip.classList.remove('mopdp-tooltip-visible');
    }

    function resetForm() {
        if (emailInput) emailInput.value = '';
        if (phoneInput) phoneInput.value = '';
        if (queryInput) queryInput.value = '';
        clearError();
        if (formBody) formBody.style.display = '';
        if (successMsg) {
            successMsg.style.display = 'none';
            successMsg.style.minHeight = '';
            successMsg.style.flexDirection = '';
            successMsg.style.alignItems = '';
            successMsg.style.justifyContent = '';
        }
        if (submitBtn) submitBtn.disabled = false;
    }

    // ── Submit handler ───────────────────────────────────────

    function handleSubmit() {
        clearError();

        var email = emailInput ? emailInput.value.trim() : '';
        var phone = phoneInput ? phoneInput.value.trim() : '';
        var query = queryInput ? queryInput.value.trim() : '';

        if (!email) {
            showError('Email is required. Please enter a valid email address.');
            return;
        }

        if (!query) {
            showError('Please describe your issue in the query field.');
            return;
        }

        var submitUrl = (window.MOPDP_Support && window.MOPDP_Support.submitUrl) || '';
        if (!submitUrl) {
            showError('Configuration error. Please reload the page.');
            return;
        }

        if (submitBtn) submitBtn.disabled = true;

        var url = submitUrl
            + (submitUrl.indexOf('?') === -1 ? '?' : '&')
            + 'email=' + encodeURIComponent(email)
            + '&phone=' + encodeURIComponent(phone)
            + '&query=' + encodeURIComponent(query);

        fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    // Lock success msg to same height as form body so panel doesn't shrink
                    if (formBody && successMsg) {
                        successMsg.style.minHeight = formBody.offsetHeight + 'px';
                        successMsg.style.display = 'flex';
                        successMsg.style.flexDirection = 'column';
                        successMsg.style.alignItems = 'center';
                        successMsg.style.justifyContent = 'center';
                    } else if (successMsg) {
                        successMsg.style.display = 'block';
                    }
                    if (formBody) formBody.style.display = 'none';
                    setTimeout(function () {
                        resetForm();
                        closeForm();
                    }, 5000);
                } else {
                    showError((data && data.message) || 'Something went wrong. Please try again.');
                    if (submitBtn) submitBtn.disabled = false;
                }
            })
            .catch(function () {
                showError('Network error. Please check your connection and try again.');
                if (submitBtn) submitBtn.disabled = false;
            });
    }

    // ── First-visit tooltip logic ────────────────────────────

    function initTooltip() {
        var visited = false;
        try { visited = !!localStorage.getItem('mopdp_support_visited'); } catch (e) {}

        if (!visited) {
            // Show tooltip for 8 s on first visit, then mark visited
            setTimeout(function () { showTooltip(); }, 800);
            setTimeout(function () { hideTooltip(); }, 8800);
            try { localStorage.setItem('mopdp_support_visited', '1'); } catch (e) {}
        }

        // Hover tooltip on subsequent visits
        if (icon) {
            icon.addEventListener('mouseenter', function () {
                if (!form || !form.classList.contains('mopdp-form-open')) {
                    showTooltip();
                }
            });
            icon.addEventListener('mouseleave', hideTooltip);
        }
    }

    // ── Boot ─────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', function () {
        icon      = document.getElementById('mopdp-support-icon');
        form      = document.getElementById('mopdp-support-form');
        closeBtn  = document.getElementById('mopdp-support-close');
        emailInput = document.getElementById('mopdp-support-email');
        phoneInput = document.getElementById('mopdp-support-phone');
        queryInput = document.getElementById('mopdp-support-query');
        errorBox  = document.getElementById('mopdp-support-error');
        formBody  = document.getElementById('mopdp-support-form-body');
        successMsg = document.getElementById('mopdp-success-msg');
        submitBtn = document.getElementById('mopdp-support-submit');
        tooltip   = document.getElementById('mopdp-help-tooltip');

        if (icon) {
            icon.addEventListener('click', function () {
                if (form && form.classList.contains('mopdp-form-open')) {
                    closeForm();
                } else {
                    openForm();
                }
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                closeForm();
            });
        }

        if (submitBtn) {
            submitBtn.addEventListener('click', handleSubmit);
        }

        initTooltip();
    });
}());
