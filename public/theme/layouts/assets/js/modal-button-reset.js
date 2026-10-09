/*
 * Safety net for modal submit buttons that are left in a "loading" state.
 *
 * Many pages put a modal's submit button into a loading state (spinner,
 * "Saving…", disabled) while an AJAX request runs, then hide the modal on
 * success without restoring the button. The next time the modal opens the
 * button still spins until the page is reloaded.
 *
 * This snapshots every button inside a modal the first time the modal opens
 * (while it is idle) and, on every later open, restores any button that still
 * looks like it is loading. It runs in the capture phase so it happens before
 * the page's own show handlers, which can still adjust the button afterwards.
 */
(function () {
    'use strict';

    var BUTTONS = 'button, input[type="submit"], .btn';
    var LOADING_MARKERS = '.spinner-border, .spinner-grow, .fa-spin, .spin, .btn-spinner';

    function looksLoading(btn) {
        if (btn.classList.contains('btn-loading')) return true;
        if (btn.getAttribute('aria-busy') === 'true') return true;
        return !!btn.querySelector(LOADING_MARKERS);
    }

    function snapshot(btn) {
        if (btn.__idleState || looksLoading(btn)) return;
        btn.__idleState = {
            html: btn.tagName === 'INPUT' ? null : btn.innerHTML,
            value: btn.tagName === 'INPUT' ? btn.value : null,
            disabled: btn.hasAttribute('disabled')
        };
    }

    function restore(btn) {
        var s = btn.__idleState;
        if (!s || !looksLoading(btn)) return;
        if (s.html !== null) btn.innerHTML = s.html;
        if (s.value !== null) btn.value = s.value;
        btn.classList.remove('btn-loading');
        btn.removeAttribute('aria-busy');
        btn.disabled = s.disabled;
    }

    function eachButton(modal, fn) {
        if (!modal || !modal.querySelectorAll) return;
        Array.prototype.forEach.call(modal.querySelectorAll(BUTTONS), fn);
    }

    document.addEventListener('show.bs.modal', function (e) {
        eachButton(e.target, function (btn) {
            restore(btn);
            snapshot(btn);
        });
    }, true);

    document.addEventListener('hidden.bs.modal', function (e) {
        eachButton(e.target, restore);
    }, true);
})();
