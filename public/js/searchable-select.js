/*!
 * searchable-select.js — adds a search box to every ordinary <select> in the portal.
 *
 * The original <select> stays visible and remains the real form field. When it is
 * opened we show our own list (with a search box) instead of the browser's native
 * list; picking an item sets select.value and fires the normal "input"/"change"
 * events. Because the native select is never replaced, existing page code keeps
 * working: $('#x').val(5), options loaded by AJAX, disabled states, validation…
 *
 * Skipped automatically: multiple/size>1 lists, Select2 / Choices.js selects,
 * DataTables "Show N entries" menus, and anything with data-no-search or
 * class="no-search".
 */
(function () {
    'use strict';
    if (window.__ssLoaded) return;
    window.__ssLoaded = true;

    var MAX_RENDER = 400;
    var panel = null, state = null;

    // ── styles ──────────────────────────────────────────────────────────
    var css = ''
        + '.ss-panel{position:fixed;z-index:2100;background:var(--tb-secondary-bg,#fff);color:var(--tb-body-color,#212529);'
        + 'border:1px solid var(--tb-border-color,#dde1ef);border-radius:10px;box-shadow:0 12px 32px rgba(15,23,42,.18);'
        + 'display:flex;flex-direction:column;overflow:hidden;font-size:13px;min-width:200px}'
        + '.ss-panel .ss-search-wrap{position:relative;padding:8px;border-bottom:1px solid var(--tb-border-color,#e5e7eb)}'
        + '.ss-panel .ss-search-wrap svg{position:absolute;left:18px;top:50%;transform:translateY(-50%);opacity:.5;pointer-events:none}'
        + '.ss-panel .ss-search{width:100%;border:1px solid var(--tb-border-color,#dde1ef);border-radius:7px;padding:7px 10px 7px 30px;'
        + 'font-size:13px;background:transparent;color:inherit;outline:none}'
        + '.ss-panel .ss-search:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.12)}'
        + '.ss-panel ul{list-style:none;margin:0;padding:4px;overflow-y:auto;flex:1}'
        + '.ss-panel li{padding:7px 10px;border-radius:6px;cursor:pointer;line-height:1.3;white-space:normal;word-break:break-word}'
        + '.ss-panel li.ss-group{cursor:default;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;opacity:.6;padding:8px 10px 4px}'
        + '.ss-panel li.ss-opt.ss-active{background:rgba(37,99,235,.10)}'
        + '.ss-panel li.ss-opt.ss-selected{font-weight:600;color:#2563eb}'
        + '.ss-panel li.ss-opt.ss-selected::after{content:"\\2713";float:right;margin-left:8px}'
        + '.ss-panel li.ss-opt.ss-disabled{opacity:.45;cursor:not-allowed}'
        + '.ss-panel li.ss-empty,.ss-panel li.ss-more{cursor:default;opacity:.6;font-style:italic;text-align:center}'
        + '.ss-panel mark{background:rgba(250,204,21,.45);color:inherit;padding:0;border-radius:2px}';
    var style = document.createElement('style');
    style.id = 'ss-style';
    style.textContent = css;
    (document.head || document.documentElement).appendChild(style);

    // ── helpers ─────────────────────────────────────────────────────────
    function norm(s) {
        s = (s || '').toLowerCase();
        return s.normalize ? s.normalize('NFD').replace(/[̀-ͯ]/g, '') : s;
    }

    function eligible(el) {
        if (!el || el.tagName !== 'SELECT') return false;
        if (el.multiple || el.size > 1 || el.disabled) return false;
        if (el.hasAttribute('data-no-search') || el.classList.contains('no-search')) return false;
        if (el.classList.contains('select2-hidden-accessible') || el.hasAttribute('data-select2-id')) return false;
        if (el.classList.contains('choices__input') || el.hasAttribute('data-choice') || (el.closest && el.closest('.choices'))) return false;
        if (el.closest && el.closest('.dataTables_length, .dt-length, [data-no-search]')) return false;
        return true;
    }

    function esc(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function highlight(text, words) {
        if (!words.length) return esc(text);
        var n = norm(text), marks = [];
        words.forEach(function (w) {
            var i = n.indexOf(w);
            if (i > -1) marks.push([i, i + w.length]);
        });
        if (!marks.length) return esc(text);
        marks.sort(function (a, b) { return a[0] - b[0]; });
        var out = '', pos = 0;
        marks.forEach(function (m) {
            if (m[0] < pos) return;
            out += esc(text.slice(pos, m[0])) + '<mark>' + esc(text.slice(m[0], m[1])) + '</mark>';
            pos = m[1];
        });
        return out + esc(text.slice(pos));
    }

    // ── panel ───────────────────────────────────────────────────────────
    function open(select) {
        close();
        var items = [];
        Array.prototype.forEach.call(select.options, function (opt, idx) {
            if (opt.hidden || opt.style.display === 'none') return;
            var grp = opt.parentNode && opt.parentNode.tagName === 'OPTGROUP' ? opt.parentNode.label : '';
            items.push({ idx: idx, text: (opt.text || '').trim(), key: norm(opt.text), disabled: opt.disabled || (opt.parentNode && opt.parentNode.disabled), group: grp });
        });

        panel = document.createElement('div');
        panel.className = 'ss-panel';
        panel.setAttribute('role', 'listbox');
        panel.innerHTML = '<div class="ss-search-wrap">'
            + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>'
            + '<input type="text" class="ss-search" placeholder="Search…" autocomplete="off" spellcheck="false"></div><ul></ul>';

        var host = (select.closest && select.closest('.modal, .offcanvas')) || document.body;
        host.appendChild(panel);

        state = { select: select, items: items, input: panel.querySelector('.ss-search'), list: panel.querySelector('ul'), visible: [], active: -1 };

        state.input.addEventListener('input', render);
        state.input.addEventListener('keydown', onKey);
        state.list.addEventListener('mousedown', function (e) { e.preventDefault(); });
        state.list.addEventListener('click', function (e) {
            var li = e.target.closest('li.ss-opt');
            if (li && !li.classList.contains('ss-disabled')) choose(+li.getAttribute('data-idx'));
        });
        state.list.addEventListener('mousemove', function (e) {
            var li = e.target.closest('li.ss-opt');
            if (!li) return;
            var i = state.visible.indexOf(+li.getAttribute('data-idx'));
            if (i !== state.active) setActive(i, false);
        });

        render();
        position();
        state.input.focus({ preventScroll: true });
        setTimeout(function () { if (state && document.activeElement !== state.input) state.input.focus({ preventScroll: true }); }, 0);
    }

    function render() {
        var words = norm(state.input.value).split(/\s+/).filter(Boolean);
        var sel = state.select.selectedIndex;
        var html = '', shown = 0, lastGroup = null, matches = 0;
        state.visible = [];
        for (var i = 0; i < state.items.length; i++) {
            var it = state.items[i];
            var ok = true;
            for (var w = 0; w < words.length; w++) if (it.key.indexOf(words[w]) === -1) { ok = false; break; }
            if (!ok) continue;
            matches++;
            if (shown >= MAX_RENDER) continue;
            if (it.group && it.group !== lastGroup) { html += '<li class="ss-group">' + esc(it.group) + '</li>'; lastGroup = it.group; }
            html += '<li class="ss-opt' + (it.idx === sel ? ' ss-selected' : '') + (it.disabled ? ' ss-disabled' : '')
                + '" data-idx="' + it.idx + '" role="option">' + (it.text ? highlight(it.text, words) : '&nbsp;') + '</li>';
            if (!it.disabled) state.visible.push(it.idx);
            shown++;
        }
        if (!matches) html = '<li class="ss-empty">No matches</li>';
        else if (matches > shown) html += '<li class="ss-more">' + (matches - shown) + ' more — keep typing to narrow down</li>';
        state.list.innerHTML = html;
        var start = words.length ? 0 : Math.max(0, state.visible.indexOf(sel));
        setActive(state.visible.length ? start : -1, true);
    }

    function setActive(i, scroll) {
        state.active = i;
        var prev = state.list.querySelector('.ss-active');
        if (prev) prev.classList.remove('ss-active');
        if (i < 0) return;
        var li = state.list.querySelector('li[data-idx="' + state.visible[i] + '"]');
        if (li) {
            li.classList.add('ss-active');
            if (scroll) li.scrollIntoView({ block: 'nearest' });
        }
    }

    function onKey(e) {
        if (!state) return;
        var n = state.visible.length;
        if (e.key === 'ArrowDown') { e.preventDefault(); if (n) setActive((state.active + 1) % n, true); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); if (n) setActive((state.active - 1 + n) % n, true); }
        else if (e.key === 'Enter') { e.preventDefault(); if (state.active > -1) choose(state.visible[state.active]); }
        else if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); var s = state.select; close(); s.focus(); }
        else if (e.key === 'Tab') { var s2 = state.select; close(); s2.focus(); }
    }

    function choose(idx) {
        var select = state.select;
        var changed = select.selectedIndex !== idx;
        close();
        select.selectedIndex = idx;
        select.focus({ preventScroll: true });
        if (changed) {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function position() {
        if (!state) return;
        var r = state.select.getBoundingClientRect();
        if (!r.width && !r.height) { close(); return; }
        var vw = window.innerWidth, vh = window.innerHeight;
        var width = Math.min(Math.max(r.width, 240), vw - 16);
        var below = vh - r.bottom - 8, above = r.top - 8;
        var up = below < 260 && above > below;
        var maxH = Math.min(360, Math.max(160, up ? above : below));
        var left = Math.min(Math.max(8, r.left), vw - width - 8);
        panel.style.width = width + 'px';
        panel.style.maxHeight = maxH + 'px';
        panel.style.left = left + 'px';
        if (up) { panel.style.top = ''; panel.style.bottom = (vh - r.top + 4) + 'px'; }
        else { panel.style.bottom = ''; panel.style.top = (r.bottom + 4) + 'px'; }
    }

    function close() {
        if (panel && panel.parentNode) panel.parentNode.removeChild(panel);
        panel = null;
        state = null;
    }

    // ── triggers ────────────────────────────────────────────────────────
    // Desktop: stop the native list from opening and show ours.
    document.addEventListener('mousedown', function (e) {
        if (panel && !panel.contains(e.target)) {
            var wasFor = state && state.select;
            close();
            if (wasFor === e.target) { e.preventDefault(); return; } // click again on the select = toggle closed
        }
        var t = e.target;
        if (e.button !== 0 || !eligible(t)) return;
        e.preventDefault();
        t.focus({ preventScroll: true });
        open(t);
    }, true);

    // Touch: open on a tap (not on a scroll gesture) instead of the native picker.
    var touch = null;
    document.addEventListener('touchstart', function (e) {
        var t = e.target;
        touch = eligible(t) ? { el: t, x: e.touches[0].clientX, y: e.touches[0].clientY } : null;
    }, { capture: true, passive: true });
    document.addEventListener('touchend', function (e) {
        if (!touch || e.target !== touch.el) { touch = null; return; }
        var p = e.changedTouches[0];
        var moved = Math.abs(p.clientX - touch.x) > 10 || Math.abs(p.clientY - touch.y) > 10;
        var el = touch.el;
        touch = null;
        if (moved) return;
        e.preventDefault();
        open(el);
    }, { capture: true, passive: false });

    // Keyboard: Space / Enter / Alt+Down / F4 on a focused select opens the search list.
    document.addEventListener('keydown', function (e) {
        var t = e.target;
        if (panel || !eligible(t)) return;
        if (e.key === ' ' || e.key === 'Enter' || e.key === 'F4' || (e.altKey && (e.key === 'ArrowDown' || e.key === 'ArrowUp'))) {
            e.preventDefault();
            open(t);
        }
    }, true);

    window.addEventListener('resize', position);
    document.addEventListener('scroll', function (e) {
        if (panel && !panel.contains(e.target)) position();
    }, true);
    // Close if the page hides the select (modal closed, tab switched…)
    document.addEventListener('hide.bs.modal', function () { close(); });

    window.SearchableSelect = { open: open, close: close };
})();
