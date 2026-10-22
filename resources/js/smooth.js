/**
 * Animasi halus global (Web Animations API).
 *
 * Banyak halaman membuka/menutup modal, panel, dan peringatan dengan
 * `el.style.display = 'flex' | 'none'`, `classList.toggle('hidden')`, atau
 * `x-show` tanpa `x-transition` — hasilnya muncul/hilang patah-patah.
 * Modul ini mengamati perubahan itu lewat MutationObserver dan menambahkan
 * animasi otomatis, tanpa perlu mengubah kode tiap halaman:
 *
 * - Overlay modal layar penuh : latar memudar + kotak dialog "pop" (skala).
 * - Elemen melayang (dropdown, bar aksi fixed) : fade + geser sedikit.
 * - Blok di alur halaman : tinggi membuka/menutup (expand/collapse) + fade.
 * - Elemen inline / di dalam baris flex/grid : fade saja.
 *
 * Saat menutup, elemen ditampilkan kembali sementara selama animasi keluar,
 * lalu disembunyikan lagi — kode halaman tidak perlu tahu.
 *
 * Dilewati: elemen yang sudah punya x-transition / x-collapse (Alpine yang
 * menganimasikan), elemen baru masuk DOM, `[data-no-animate]`, dan pengguna
 * dengan pengaturan "kurangi gerakan" (prefers-reduced-motion).
 */

const EASE_OUT = 'cubic-bezier(.16, 1, .3, 1)';
const EASE_IN = 'cubic-bezier(.4, 0, 1, 1)';
const SKIP_TAGS = new Set(['TR', 'TD', 'TH', 'TBODY', 'THEAD', 'OPTION', 'SCRIPT', 'TEMPLATE', 'STYLE', 'LINK', 'META', 'BR']);
const MAX_HEIGHT_ANIM = 520; // blok lebih tinggi dari ini cukup di-fade (lebih ringan)

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const running = new WeakMap(); // el -> { type: 'show' | 'hide', anim, finishHide }
const addedAt = new WeakMap(); // el -> waktu masuk DOM
let observer = null;
let enabled = false;

/* ── util ─────────────────────────────────────────────────────────────── */

function inlineDisplay(styleText) {
    const m = /(?:^|;)\s*display\s*:\s*([^;!]+)/i.exec(styleText || '');
    return m ? m[1].trim().toLowerCase() : '';
}

function hasClassToken(classText, token) {
    return (' ' + (classText || '') + ' ').replace(/\s+/g, ' ').includes(' ' + token + ' ');
}

function hasAttrPrefix(el, ...prefixes) {
    for (const a of el.attributes) {
        if (prefixes.some(p => a.name.startsWith(p))) return true;
    }
    return false;
}

function alpineAnimated(el) {
    return hasAttrPrefix(el, 'x-transition', 'x-collapse');
}

function recentlyAdded(el) {
    const now = performance.now();
    for (let n = el; n && n.nodeType === 1; n = n.parentElement) {
        const t = addedAt.get(n);
        if (t !== undefined && now - t < 150) return true;
    }
    return false;
}

function isRendered(el) {
    return el.isConnected && el.getClientRects().length > 0;
}

/** Jalankan perubahan gaya milik modul ini tanpa memicu observer lagi. */
function silently(el, fn) {
    fn();
    if (!observer) return;
    const pending = observer.takeRecords().filter(r => r.target !== el);
    if (pending.length) handle(pending);
}

/** Jenis animasi berdasarkan posisi/tata letak elemen saat terlihat. */
function kindOf(el) {
    const cs = getComputedStyle(el);
    if (cs.position === 'fixed' || cs.position === 'absolute' || cs.position === 'sticky') {
        const r = el.getBoundingClientRect();
        if (cs.position === 'fixed' && r.width >= window.innerWidth * 0.9 && r.height >= window.innerHeight * 0.9) {
            return 'overlay';
        }
        return cs.position === 'sticky' ? 'fade' : 'float';
    }
    if (cs.display.startsWith('inline') || cs.display.startsWith('table') || cs.display === 'contents') return 'fade';
    const parent = el.parentElement;
    if (parent) {
        const pcs = getComputedStyle(parent);
        if (pcs.display.includes('grid')) return 'fade';
        if (pcs.display.includes('flex') && pcs.flexDirection.startsWith('row')) return 'fade';
    }
    if (el.offsetHeight > MAX_HEIGHT_ANIM) return 'slide';
    return 'height';
}

/** Kotak dialog di dalam overlay modal (untuk efek "pop"). */
function dialogOf(overlay) {
    const explicit = overlay.querySelector('.modal-box, [role="dialog"], [data-modal-box]');
    if (explicit) return explicit;
    let node = overlay;
    for (let depth = 0; depth < 3; depth++) {
        const kids = Array.from(node.children).filter(c => {
            if (SKIP_TAGS.has(c.tagName)) return false;
            const cs = getComputedStyle(c);
            return cs.display !== 'none' && cs.position !== 'fixed';
        });
        if (kids.length !== 1) return kids[kids.length - 1] || null;
        node = kids[0];
        const bg = getComputedStyle(node).backgroundColor;
        if (bg && bg !== 'transparent' && !/rgba\(.*,\s*0\)$/.test(bg)) return node;
    }
    return node === overlay ? null : node;
}

function boxKeyframes(el) {
    const cs = getComputedStyle(el);
    return {
        height: cs.height,
        paddingTop: cs.paddingTop,
        paddingBottom: cs.paddingBottom,
        marginTop: cs.marginTop,
        marginBottom: cs.marginBottom,
    };
}

const COLLAPSED = { height: '0px', paddingTop: '0px', paddingBottom: '0px', marginTop: '0px', marginBottom: '0px' };

const savedOverflow = new WeakMap(); // el -> { value, anim } nilai overflow asli
function withOverflowHidden(el, anim) {
    const saved = savedOverflow.get(el);
    savedOverflow.set(el, { value: saved ? saved.value : el.style.overflow, anim });
    silently(el, () => { el.style.overflow = 'hidden'; });
    const restore = () => {
        const cur = savedOverflow.get(el);
        if (!cur || cur.anim !== anim) return; // animasi lebih baru yang akan memulihkan
        savedOverflow.delete(el);
        silently(el, () => { el.style.overflow = cur.value; });
    };
    anim.addEventListener('finish', restore, { once: true });
    anim.addEventListener('cancel', restore, { once: true });
    setTimeout(restore, (Number(anim.effect.getTiming().duration) || 200) + 200);
}

/* ── animasi ──────────────────────────────────────────────────────────── */

function animateShow(el) {
    const kind = kindOf(el);
    let anim;

    if (kind === 'overlay') {
        anim = el.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 200, easing: 'ease-out' });
        const box = dialogOf(el);
        if (box && !alpineAnimated(box)) {
            box.animate(
                [{ opacity: 0, transform: 'translateY(14px) scale(.96)' }, { opacity: 1, transform: 'none' }],
                { duration: 300, easing: EASE_OUT }
            );
        }
    } else if (kind === 'float') {
        anim = el.animate(
            [{ opacity: 0, transform: 'translateY(-6px) scale(.98)' }, { opacity: 1, transform: 'none' }],
            { duration: 180, easing: EASE_OUT }
        );
    } else if (kind === 'height') {
        const full = boxKeyframes(el);
        anim = el.animate(
            [{ ...COLLAPSED, opacity: 0 }, { ...full, opacity: 1 }],
            { duration: 260, easing: EASE_OUT }
        );
        withOverflowHidden(el, anim);
    } else if (kind === 'slide') {
        anim = el.animate(
            [{ opacity: 0, transform: 'translateY(8px)' }, { opacity: 1, transform: 'none' }],
            { duration: 240, easing: EASE_OUT }
        );
    } else {
        anim = el.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 160, easing: 'ease-out' });
    }

    running.set(el, { type: 'show', anim });
    anim.addEventListener('finish', () => {
        if (running.get(el)?.anim === anim) running.delete(el);
    }, { once: true });
}

/**
 * @param reveal   fungsi yang menampilkan kembali elemen sementara
 * @param conceal  fungsi yang menyembunyikannya lagi (state akhir dari halaman)
 */
function animateHide(el, reveal, conceal) {
    silently(el, reveal);
    if (!isRendered(el)) { silently(el, conceal); return; }

    const kind = kindOf(el);
    let anim;

    if (kind === 'overlay') {
        anim = el.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 180, easing: 'ease-in' });
        const box = dialogOf(el);
        if (box && !alpineAnimated(box)) {
            box.animate(
                [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(8px) scale(.97)' }],
                { duration: 180, easing: EASE_IN, fill: 'forwards' }
            ).addEventListener('finish', function () { this.cancel(); }, { once: true });
        }
    } else if (kind === 'float') {
        anim = el.animate(
            [{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'translateY(-4px) scale(.98)' }],
            { duration: 130, easing: EASE_IN }
        );
    } else if (kind === 'height') {
        anim = el.animate(
            [{ ...boxKeyframes(el), opacity: 1 }, { ...COLLAPSED, opacity: 0 }],
            { duration: 200, easing: 'cubic-bezier(.4, 0, .2, 1)' }
        );
        withOverflowHidden(el, anim);
    } else {
        anim = el.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 130, easing: EASE_IN });
    }

    // Tahan state akhir (tak terlihat) sampai elemen benar-benar disembunyikan.
    anim.effect.updateTiming({ fill: 'forwards' });

    const state = { type: 'hide', anim, reveal, conceal };
    running.set(el, state);

    const complete = () => {
        if (running.get(el) !== state) return;
        running.delete(el);
        silently(el, state.conceal);
        anim.cancel();
    };
    anim.addEventListener('finish', complete, { once: true });

    // Cadangan: di tab latar belakang timeline animasi bisa berhenti — pastikan
    // elemen tetap tersembunyi tepat waktu.
    const ms = Number(anim.effect.getTiming().duration) || 200;
    setTimeout(complete, ms + 150);
}

/* ── observer ─────────────────────────────────────────────────────────── */

function changeOf(record) {
    const el = record.target;
    if (record.attributeName === 'style') {
        const before = inlineDisplay(record.oldValue);
        const after = (el.style.display || '').toLowerCase();
        if ((before === 'none') === (after === 'none')) return null;
        return after === 'none'
            ? {
                el,
                type: 'hide',
                // Ditampilkan dengan !important sebagai penanda: kalau halaman menulis
                // display lagi, penanda hilang dan kita tahu itu perintah halaman.
                reveal: () => {
                    el.style.removeProperty('display');
                    const d = before || getComputedStyle(el).display;
                    if (d !== 'none') el.style.setProperty('display', d, 'important');
                },
                conceal: () => { el.style.display = 'none'; },
            }
            : { el, type: 'show' };
    }
    if (record.attributeName === 'class') {
        if (el.style.display) return null; // display inline menang atas class
        const before = hasClassToken(record.oldValue, 'hidden');
        const after = el.classList.contains('hidden');
        if (before === after) return null;
        return after
            ? {
                el,
                type: 'hide',
                reveal: () => el.classList.remove('hidden'),
                conceal: () => el.classList.add('hidden'),
            }
            : { el, type: 'show' };
    }
    return null;
}

function skip(el) {
    if (!enabled || reduceMotion.matches) return true;
    if (!(el instanceof HTMLElement) || !el.isConnected) return true;
    if (SKIP_TAGS.has(el.tagName)) return true;
    if (el.closest('[data-no-animate]')) return true;
    if (alpineAnimated(el)) return true;
    // Wadah x-show yang anak-anaknya punya x-transition sendiri (mis. <x-modal>).
    if (el.querySelector('[x-transition], [x-transition\\:enter], [x-collapse]')) return true;
    if (recentlyAdded(el)) return true;
    return false;
}

function handle(records) {
    const now = performance.now();
    const changes = [];

    for (const r of records) {
        if (r.type === 'childList') {
            r.addedNodes.forEach(n => { if (n.nodeType === 1) addedAt.set(n, now); });
            continue;
        }
        const el = r.target;
        const active = running.get(el);
        const c = changeOf(r);

        // Elemen sedang dianimasikan keluar.
        if (active?.type === 'hide') {
            if (r.attributeName === 'style') {
                if (el.style.display === 'none') {
                    // Disembunyikan lagi: tetap tampil sampai animasi keluar selesai.
                    silently(el, active.reveal);
                } else if (el.style.display && el.style.getPropertyPriority('display') !== 'important') {
                    // Halaman membukanya lagi di tengah animasi keluar: batalkan.
                    running.delete(el);
                    active.anim.cancel();
                }
            } else if (r.attributeName === 'class' && el.classList.contains('hidden') && !el.style.display) {
                silently(el, active.reveal);
            }
            continue;
        }
        if (!c) continue;
        changes.push(c);
    }

    if (!changes.length) return;

    // Tab/ganti panel: ada elemen saudara yang muncul bersamaan → sembunyikan langsung
    // supaya dua panel tidak tampil berdempetan.
    const shownParents = new Set(changes.filter(c => c.type === 'show').map(c => c.el.parentElement));

    for (const c of changes) {
        if (skip(c.el)) continue;
        if (c.type === 'show') {
            const active = running.get(c.el);
            if (active) { running.delete(c.el); active.anim.cancel(); }
            if (!isRendered(c.el)) continue;
            animateShow(c.el);
        } else {
            if (shownParents.has(c.el.parentElement)) continue;
            const active = running.get(c.el);
            if (active) { running.delete(c.el); active.anim.cancel(); }
            animateHide(c.el, c.reveal, c.conceal);
        }
    }
}

/**
 * Jalankan perubahan DOM massal (mis. morph hasil filter) tanpa animasi.
 */
export function withoutAnimation(fn) {
    const result = fn();
    observer?.takeRecords();
    return result;
}

export function initSmooth() {
    if (observer || typeof window.MutationObserver === 'undefined' || !Element.prototype.animate) return;

    observer = new MutationObserver(handle);
    observer.observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeOldValue: true,
        attributeFilter: ['style', 'class'],
    });

    // Mulai setelah Alpine selesai inisialisasi state awal halaman.
    setTimeout(() => {
        observer.takeRecords();
        enabled = true;
    }, 60);

    window.withoutAnimation = withoutAnimation;
}
