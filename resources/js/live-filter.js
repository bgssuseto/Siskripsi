/**
 * Live search & filter untuk semua form filter (GET) di dalam #page-content.
 *
 * - Mengetik di kolom teks/pencarian langsung memfilter (debounce), tanpa Enter.
 * - Mengubah select / tanggal / checkbox di filter (termasuk Filter Lanjutan)
 *   langsung diterapkan.
 * - Halaman yang sudah punya handler submit sendiri (mis. @submit.prevent=
 *   "submitSearch(...)") tetap memakai handler itu — kita cukup memicu
 *   requestSubmit(). Halaman lain diambil via fetch lalu DOM-nya di-"morph"
 *   (Alpine Morph) supaya fokus/kursor di input, state Alpine, modal & widget
 *   JS (kalender, dll.) tidak hilang.
 * - Link paginasi di dalam #page-content juga dimuat tanpa reload.
 * - Opt-out per form: data-live="off".
 */

const DEBOUNCE_MS = 400;
const FOCUS_KEY = 'live-filter-focus';

let controller = null;
let progressEl = null;

function isLiveForm(form) {
    if (!form || form.tagName !== 'FORM') return false;
    if ((form.getAttribute('method') || '').toLowerCase() !== 'get') return false;
    if (form.dataset.live === 'off') return false;
    if (!form.closest('#page-content')) return false;
    const action = new URL(form.action || window.location.href, window.location.href);
    // Hanya form yang memfilter halaman ini sendiri — form GET ke route lain
    // (mis. export Excel) tetap berjalan normal.
    return action.origin === window.location.origin && action.pathname === window.location.pathname;
}

function hasOwnHandler(el, eventName) {
    return Array.from(el.attributes).some(a =>
        a.name.startsWith('@' + eventName) || a.name.startsWith('x-on:' + eventName) || a.name === 'on' + eventName
    );
}

function buildUrl(form) {
    const url = new URL(form.action || window.location.href, window.location.href);
    url.search = '';
    for (const [key, value] of new FormData(form).entries()) {
        if (typeof value === 'string' && value.trim() !== '') {
            url.searchParams.append(key, value);
        }
    }
    url.searchParams.delete('page');
    return url;
}

function showProgress(on) {
    if (!progressEl) {
        progressEl = document.createElement('div');
        progressEl.className = 'live-filter-progress';
        document.body.appendChild(progressEl);
    }
    progressEl.classList.toggle('is-active', on);
    document.getElementById('page-content')?.classList.toggle('live-filter-loading', on);
}

function rememberFocus() {
    const el = document.activeElement;
    if (!el || !el.name || !el.closest('#page-content form')) return;
    try {
        sessionStorage.setItem(FOCUS_KEY, JSON.stringify({
            name: el.name,
            start: el.selectionStart ?? null,
            path: window.location.pathname,
        }));
    } catch (e) { /* storage tidak tersedia */ }
}

function restoreFocus() {
    let saved = null;
    try {
        saved = JSON.parse(sessionStorage.getItem(FOCUS_KEY) || 'null');
        sessionStorage.removeItem(FOCUS_KEY);
    } catch (e) { return; }
    if (!saved || saved.path !== window.location.pathname) return;
    const el = document.querySelector(`#page-content form [name="${CSS.escape(saved.name)}"]`);
    if (!el) return;
    el.focus();
    if (saved.start !== null && typeof el.setSelectionRange === 'function') {
        try { el.setSelectionRange(saved.start, saved.start); } catch (e) { /* type tanpa selection */ }
    }
}

function executableScripts(root) {
    return Array.from(root.querySelectorAll('script'))
        .filter(s => !s.type || s.type === 'text/javascript' || s.type === 'module')
        .map(s => s.textContent.trim())
        .join('\n');
}

function fullNavigate(url) {
    rememberFocus();
    window.location.assign(url.toString());
}

async function liveLoad(url, { scrollToTop = false } = {}) {
    const current = document.getElementById('page-content');
    if (!current || !window.Alpine?.morph) return fullNavigate(url);

    controller?.abort();
    controller = new AbortController();
    showProgress(true);

    try {
        const res = await fetch(url.toString(), {
            signal: controller.signal,
            headers: { 'X-Live-Filter': '1', 'Accept': 'text/html' },
            credentials: 'same-origin',
        });
        if (!res.ok || res.redirected) return fullNavigate(url);

        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const next = doc.getElementById('page-content');
        if (!next) return fullNavigate(url);

        // Script inline yang ikut berubah berarti halaman menyuntikkan data hasil
        // filter lewat JS — morph tidak menjalankan ulang script, jadi muat penuh.
        if (executableScripts(current) !== executableScripts(next)) return fullNavigate(url);

        const active = document.activeElement;
        window.Alpine.morph(current, next, {
            updating(el, toEl, childrenOnly, skip) {
                if (el.nodeType !== 1) return;
                if (el === active && /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName)) return skip();
                if (el.dataset?.liveKeep !== undefined) return skip();
                // Modal yang sedang dibuka JS jangan ditutup paksa.
                if (el.classList?.contains('modal-overlay')) return skip();
                // Wadah widget yang diisi JS (kalender, chart, daftar dinamis):
                // versi server kosong, versi di browser sudah berisi.
                if (el.children.length > 0 && toEl.children.length === 0 && toEl.textContent.trim() === '') return skip();
            },
        });

        window.history.replaceState(window.history.state, '', url.toString());
        if (scrollToTop) current.scrollIntoView({ behavior: 'smooth', block: 'start' });
    } catch (err) {
        if (err.name !== 'AbortError') fullNavigate(url);
    } finally {
        showProgress(false);
    }
}

function trigger(form) {
    if (hasOwnHandler(form, 'submit')) {
        form.requestSubmit();
    } else {
        liveLoad(buildUrl(form));
    }
}

const timers = new WeakMap();

export function initLiveFilter() {
    restoreFocus();

    document.addEventListener('input', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement)) return;
        if (!['text', 'search', 'number', 'textarea'].includes(el.type)) return;
        const form = el.form;
        if (!isLiveForm(form) || hasOwnHandler(el, 'input')) return;
        clearTimeout(timers.get(form));
        timers.set(form, setTimeout(() => trigger(form), DEBOUNCE_MS));
    });

    document.addEventListener('change', (e) => {
        const el = e.target;
        if (!(el instanceof HTMLSelectElement || el instanceof HTMLInputElement)) return;
        if (el instanceof HTMLInputElement && ['text', 'search', 'number'].includes(el.type)) return;
        const form = el.form;
        if (!isLiveForm(form) || hasOwnHandler(el, 'change')) return;
        clearTimeout(timers.get(form));
        trigger(form);
    });

    // Enter / tombol "Filter" / "Terapkan" pada form tanpa handler sendiri.
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || !isLiveForm(form)) return;
        e.preventDefault();
        clearTimeout(timers.get(form));
        liveLoad(buildUrl(form));
    });

    // Paginasi tanpa reload.
    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target.closest('#page-content nav[role="navigation"] a[href], #page-content .pagination a[href], #page-content .pagination-wrap a[href]');
        if (!link || link.target) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
        e.preventDefault();
        liveLoad(url, { scrollToTop: true });
    });
}
