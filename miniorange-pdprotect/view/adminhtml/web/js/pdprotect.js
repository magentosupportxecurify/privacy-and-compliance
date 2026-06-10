/* MiniOrange PDProtect - Admin JS */

function mopdpToggleCleanPeriod(checkbox) {
    var row = document.getElementById('mopdp-clean-period-row');
    if (row) {
        row.style.display = checkbox.checked ? '' : 'none';
    }
}

function mopdpToggleAllowedCountries(value) {
    var row = document.getElementById('mopdp-country-select-row');
    if (row) {
        row.style.display = (value === 'specific') ? '' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    /* ripple effect on primary buttons */
    document.querySelectorAll('.mopdp-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var ripple = document.createElement('span');
            var rect   = btn.getBoundingClientRect();
            var size   = Math.max(rect.width, rect.height);
            ripple.style.cssText = [
                'position:absolute',
                'border-radius:50%',
                'background:rgba(255,255,255,0.35)',
                'width:' + size + 'px',
                'height:' + size + 'px',
                'left:' + (e.clientX - rect.left - size / 2) + 'px',
                'top:' + (e.clientY - rect.top - size / 2) + 'px',
                'transform:scale(0)',
                'animation:mopdp-ripple 0.5s linear',
                'pointer-events:none'
            ].join(';');
            btn.appendChild(ripple);
            setTimeout(function () { ripple.remove(); }, 600);
        });
    });
});

/* Inject ripple keyframes once */
(function () {
    if (document.getElementById('mopdp-ripple-style')) return;
    var s = document.createElement('style');
    s.id = 'mopdp-ripple-style';
    s.textContent = '@keyframes mopdp-ripple{to{transform:scale(2.5);opacity:0}}';
    document.head.appendChild(s);
}());

/**
 * Toggle visibility of a dependent form row based on a checkbox state.
 * Used by CustomerPrivacy/index.phtml for show/hide sub-fields.
 *
 * @param {HTMLInputElement} checkbox
 * @param {string}           targetId  — id of the element to show/hide
 */
function mopdpToggle(checkbox, targetId) {
    var target = document.getElementById(targetId);
    if (target) {
        target.style.display = checkbox.checked ? '' : 'none';
    }
}
