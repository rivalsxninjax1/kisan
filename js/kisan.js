/* KISAN - small UI helpers (no libraries needed) */
(function () {
    'use strict';

    // Mobile navigation toggle
    var toggle = document.querySelector('[data-nav-toggle]');
    var header = document.querySelector('.site-header');
    if (toggle && header) {
        toggle.addEventListener('click', function () {
            var open = header.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Flash messages: dismiss button + auto-hide
    function hideFlash(el) {
        el.classList.add('is-hiding');
        setTimeout(function () { if (el.parentNode) { el.parentNode.removeChild(el); } }, 300);
    }
    document.querySelectorAll('.flash').forEach(function (el) {
        var btn = el.querySelector('.flash-close');
        if (btn) { btn.addEventListener('click', function () { hideFlash(el); }); }
        if (!el.classList.contains('flash-error')) {
            setTimeout(function () { hideFlash(el); }, 6000);
        }
    });

    // Ask before destructive actions: <form data-confirm="Are you sure?">
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (ev) {
            if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    });

    // Live "passwords match" hint on forms with data-password-match
    document.querySelectorAll('form[data-password-match]').forEach(function (form) {
        var a = form.querySelector('[data-pw-new]');
        var b = form.querySelector('[data-pw-confirm]');
        var hint = form.querySelector('[data-pw-hint]');
        if (!a || !b || !hint) { return; }
        function check() {
            if (!b.value) { hint.textContent = ''; return; }
            var ok = a.value === b.value;
            hint.textContent = ok ? 'Passwords match.' : 'Passwords do not match yet.';
            hint.className = 'hint ' + (ok ? 'hint-ok' : 'hint-bad');
        }
        a.addEventListener('input', check);
        b.addEventListener('input', check);
    });

    // Show the chosen file name on custom file inputs
    document.querySelectorAll('input[type="file"][data-file-label]').forEach(function (input) {
        var label = document.querySelector(input.getAttribute('data-file-label'));
        if (!label) { return; }
        input.addEventListener('change', function () {
            label.textContent = input.files && input.files[0] ? input.files[0].name : 'No file chosen';
        });
    });
})();
