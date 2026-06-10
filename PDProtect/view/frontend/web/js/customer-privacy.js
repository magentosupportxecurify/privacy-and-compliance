/* MiniOrange PDProtect - Customer Privacy Settings page JS (frontend)
 *
 * Intercepts clicks on `.mo-premium-action` buttons (shown on free plan) and
 * displays the `#mo-action-error` div instead of navigating away.
 *
 * No-op when there are no `.mo-premium-action` elements on the page (i.e.
 * when the store is running the premium plan and data controls are functional).
 */

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.mo-premium-action').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var errorEl = document.getElementById('mo-action-error');
            if (errorEl) {
                errorEl.style.display = 'block';
                errorEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    });
});
