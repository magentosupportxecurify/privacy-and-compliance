/* MiniOrange PDProtect - Consent Logs tab JS
 *
 * Reads config from window.MOPDP_ConsentLogs set by the phtml template:
 *   window.MOPDP_ConsentLogs = { searchBaseUrl: '...' };
 */

document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('mopdp-consent-search');
    if (!searchInput) { return; }
    var baseUrl = (window.MOPDP_ConsentLogs || {}).searchBaseUrl || '';
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var url = new URL(baseUrl);
            var q   = this.value.trim();
            if (q) {
                url.searchParams.set('search', q);
            } else {
                url.searchParams.delete('search');
            }
            window.location.href = url.toString();
        }
    });
});
