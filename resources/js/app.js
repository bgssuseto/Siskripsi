import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import collapse from '@alpinejs/collapse';
import { initLiveFilter } from './live-filter';
import { initSmooth } from './smooth';
import './calendar-kit';

Alpine.plugin(morph);
Alpine.plugin(collapse);

window.Alpine = Alpine;

// Dialog konfirmasi global, pengganti confirm() bawaan browser yang tampilannya
// tidak konsisten (beda tiap OS/browser, tidak ikut dark mode). Dipakai lewat
// window.confirmDialog(pesan, opsi) → Promise<boolean>. Dibuat sebagai satu
// elemen (singleton) dipakai ulang di semua halaman; animasinya otomatis
// ditangani smooth.js (overlay fade + dialog "pop") karena cuma toggle
// style.display seperti modal lain di aplikasi ini — tidak perlu kode animasi
// sendiri di sini. Gaya ditulis pakai utility Tailwind biasa (bukan kelas
// .modal-overlay/.modal-box lokal tiap halaman) supaya tampil konsisten di
// SEMUA halaman, termasuk yang belum punya CSS modal sendiri.
const CONFIRM_TONES = {
    danger:  { icon: '⚠️', iconBg: 'bg-rose-100 dark:bg-rose-500/15 text-rose-600 dark:text-rose-400', btn: 'bg-rose-600 hover:bg-rose-700' },
    warning: { icon: '⚠️', iconBg: 'bg-amber-100 dark:bg-amber-500/15 text-amber-600 dark:text-amber-400', btn: 'bg-amber-600 hover:bg-amber-700' },
    info:    { icon: 'ℹ️', iconBg: 'bg-indigo-100 dark:bg-indigo-500/15 text-indigo-600 dark:text-indigo-400', btn: 'bg-indigo-600 hover:bg-indigo-700' },
};

window.confirmDialog = function (message, options = {}) {
    const tone = CONFIRM_TONES[options.type] || CONFIRM_TONES.danger;
    const title = options.title || 'Konfirmasi';
    const confirmText = options.confirmText || 'Ya, Lanjutkan';
    const cancelText = options.cancelText || 'Batal';

    return new Promise((resolve) => {
        let overlay = document.getElementById('confirm-dialog-global');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'confirm-dialog-global';
            overlay.style.cssText = 'position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.55);padding:1rem;';
            overlay.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-200 dark:border-slate-700">
                    <div class="p-5 flex items-start gap-3.5">
                        <span id="confirm-dialog-icon" class="w-10 h-10 shrink-0 rounded-full flex items-center justify-center text-lg"></span>
                        <div class="min-w-0 pt-1">
                            <h3 id="confirm-dialog-title" class="text-sm font-extrabold text-slate-800 dark:text-slate-100"></h3>
                            <p id="confirm-dialog-message" class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed"></p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 px-5 py-3.5 bg-slate-50 dark:bg-slate-900/50 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" id="confirm-dialog-cancel" class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700 transition-colors"></button>
                        <button type="button" id="confirm-dialog-confirm" class="px-4 py-2 rounded-xl text-xs font-bold text-white shadow-sm transition-colors"></button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }

        const iconEl = overlay.querySelector('#confirm-dialog-icon');
        const titleEl = overlay.querySelector('#confirm-dialog-title');
        const messageEl = overlay.querySelector('#confirm-dialog-message');
        const cancelBtn = overlay.querySelector('#confirm-dialog-cancel');
        const confirmBtn = overlay.querySelector('#confirm-dialog-confirm');

        iconEl.className = 'w-10 h-10 shrink-0 rounded-full flex items-center justify-center text-lg ' + tone.iconBg;
        iconEl.textContent = tone.icon;
        titleEl.textContent = title;
        messageEl.textContent = message;
        cancelBtn.textContent = cancelText;
        confirmBtn.textContent = confirmText;
        confirmBtn.className = 'px-4 py-2 rounded-xl text-xs font-bold text-white shadow-sm transition-colors ' + tone.btn;

        const close = (result) => {
            overlay.style.display = 'none';
            document.removeEventListener('keydown', onKeydown);
            resolve(result);
        };
        const onKeydown = (e) => { if (e.key === 'Escape') close(false); };

        cancelBtn.onclick = () => close(false);
        confirmBtn.onclick = () => close(true);
        overlay.onclick = (e) => { if (e.target === overlay) close(false); };
        document.addEventListener('keydown', onKeydown);

        overlay.style.display = 'flex';
    });
};

// Helper to refresh elements dynamically via AJAX — dipakai setelah aksi
// (plotting, edit, hapus, bulk, dll) supaya halaman tidak perlu di-reload
// penuh. Isi lama di-fade-out singkat sebelum diganti, lalu fade-in —
// murni CSS opacity (ringan), dan otomatis dilewati untuk pengguna dengan
// preferensi "kurangi gerakan".
const REFRESH_FADE_MS = 120;
const reduceMotionRC = window.matchMedia('(prefers-reduced-motion: reduce)');

window.refreshComponent = async function(selectors) {
    try {
        const response = await fetch(window.location.href);
        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const targets = selectors
            .map(selector => ({ el: document.querySelector(selector), newEl: doc.querySelector(selector) }))
            .filter(t => t.el && t.newEl);

        const animate = !reduceMotionRC.matches && targets.some(t => t.el.animate);

        if (animate) {
            await Promise.all(targets.map(({ el }) =>
                el.animate([{ opacity: 1 }, { opacity: .35 }], { duration: REFRESH_FADE_MS, easing: 'ease-out' }).finished.catch(() => {})
            ));
        }

        targets.forEach(({ el, newEl }) => {
            el.innerHTML = newEl.innerHTML;
            if (window.Alpine) {
                window.Alpine.initTree(el);
            }
        });

        if (animate) {
            targets.forEach(({ el }) =>
                el.animate([{ opacity: .35 }, { opacity: 1 }], { duration: REFRESH_FADE_MS, easing: 'ease-out' })
            );
        }
        return true;
    } catch (error) {
        console.error('Error refreshing component:', error);
        return false;
    }
};

// Modal interaktif untuk menampilkan daftar bentrok jadwal (ruangan/dosen/
// mahasiswa/aturan) sebagai list per-item — dipakai di halaman Plotting
// Jadwal Sempro & Skripsi saat submit gagal karena bentrok (lihat
// SidangConflictService::checkConflicts(), yang mengembalikan array pesan
// per-bentrok, bukan satu string gabungan). Dibuat sekali (singleton) dan
// dipakai ulang lewat window.showBentrokModal(conflicts) dari tiap halaman.
function bentrokIconFor(text) {
    if (/Bentrok Ruangan/.test(text)) return '🏫';
    if (/Bentrok Dosen/.test(text)) return '👤';
    if (/Bentrok Mahasiswa/.test(text)) return '🧑‍🎓';
    if (/Rentang jam/i.test(text)) return '⏱️';
    if (/^(\[.*?\]\s*)?Aturan\b/.test(text)) return '📋';
    return '⚠️';
}

function escapeHtmlForBentrok(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function closeBentrokModal() {
    const overlay = document.getElementById('modal-bentrok-global');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
}
window.closeBentrokModal = closeBentrokModal;

window.showBentrokModal = function (conflicts) {
    if (!Array.isArray(conflicts) || conflicts.length === 0) return;

    let overlay = document.getElementById('modal-bentrok-global');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'modal-bentrok-global';
        overlay.className = 'modal-overlay';
        overlay.style.zIndex = '10000';
        overlay.innerHTML = `
            <div class="modal-box modal-sm">
                <div class="modal-header">
                    <span class="modal-title" id="modal-bentrok-title">⚠️ Jadwal Bentrok</span>
                    <button class="modal-close" type="button" data-bentrok-close>✕</button>
                </div>
                <div class="modal-body">
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Jadwal ini belum bisa disimpan karena bentrok dengan jadwal lain:</p>
                    <div id="modal-bentrok-list" class="space-y-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bentrok-close>Mengerti, Ubah Jadwal</button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeBentrokModal();
        });
        overlay.querySelectorAll('[data-bentrok-close]').forEach(function (btn) {
            btn.addEventListener('click', closeBentrokModal);
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.style.display === 'flex') closeBentrokModal();
        });
    }

    document.getElementById('modal-bentrok-title').textContent = `⚠️ Jadwal Bentrok (${conflicts.length})`;
    document.getElementById('modal-bentrok-list').innerHTML = conflicts.map(function (c) {
        return `
            <div class="flex items-start gap-2.5 p-3 bg-rose-50 dark:bg-rose-500/10 border border-rose-200 dark:border-rose-500/30 rounded-xl text-xs text-rose-800 dark:text-rose-300 leading-snug">
                <span class="text-base leading-none shrink-0">${bentrokIconFor(c)}</span>
                <span>${escapeHtmlForBentrok(c)}</span>
            </div>`;
    }).join('');

    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
};

// Let the mouse wheel scroll wide tables horizontally instead of forcing users
// to first scroll the page down to reach the scrollbar at the bottom of the table.
// Only intercepts the wheel when the hovered container actually overflows horizontally,
// so normal page scrolling is untouched everywhere else.
document.addEventListener('wheel', function (event) {
    const container = event.target.closest('.overflow-x-auto, .table-scroll');
    if (!container) return;
    if (container.scrollWidth <= container.clientWidth) return;
    if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return; // already a horizontal gesture (trackpad), let it through natively

    container.scrollLeft += event.deltaY;
    event.preventDefault();
}, { passive: false });

initSmooth();
Alpine.start();
initLiveFilter();
