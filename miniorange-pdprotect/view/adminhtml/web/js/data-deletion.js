/* MiniOrange PDProtect - Data Deletion / Delete Requests tab JS
 *
 * Reads config from window.MOPDP_JS set by the phtml template:
 *   window.MOPDP_JS = {
 *       approveTitle, rejectTitle, approve, reject,
 *       adminNote, optional, searchBaseUrl
 *   };
 */

function mopdpOpenActionModal(requestId, status, customerName) {
    var cfg       = window.MOPDP_JS || {};
    var isApprove = status === 'approved';

    document.getElementById('mopdp-modal-action-title').textContent =
        isApprove ? cfg.approveTitle : cfg.rejectTitle;
    document.getElementById('mopdp-modal-action-desc').textContent =
        (isApprove ? cfg.approve : cfg.reject) + ' the account deletion request from "' + customerName + '"?';
    document.getElementById('mopdp-modal-note-label').textContent  = cfg.adminNote + ' ';
    document.getElementById('mopdp-modal-note-optional').textContent = cfg.optional;
    document.getElementById('mopdp-action-request-id').value = requestId;
    document.getElementById('mopdp-action-status').value = status;
    document.getElementById('mopdp-action-note').value = '';

    var btn = document.getElementById('mopdp-action-submit-btn');
    btn.style.background = isApprove ? '#5cb85c' : '#d9534f';
    btn.textContent = isApprove ? cfg.approve : cfg.reject;

    document.getElementById('mopdp-action-modal').style.display = 'flex';
}

function mopdpCloseActionModal() {
    document.getElementById('mopdp-action-modal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    /* Sync admin note to hidden field on form submit */
    var form = document.getElementById('mopdp-action-form');
    if (form) {
        form.addEventListener('submit', function () {
            document.getElementById('mopdp-action-note-hidden').value =
                document.getElementById('mopdp-action-note').value;
        });
    }

    /* Search on Enter key */
    var searchInput = document.getElementById('mopdp-deletion-search');
    if (!searchInput) { return; }
    var baseUrl = (window.MOPDP_JS || {}).searchBaseUrl || '';
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var url = new URL(baseUrl);
            var q   = this.value.trim();
            if (q) { url.searchParams.set('search', q); } else { url.searchParams.delete('search'); }
            window.location.href = url.toString();
        }
    });
});
