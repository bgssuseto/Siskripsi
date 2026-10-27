import Alpine from 'alpinejs';
import morph from '@alpinejs/morph';
import collapse from '@alpinejs/collapse';
import { initLiveFilter } from './live-filter';
import { initSmooth } from './smooth';
import './calendar-kit';

Alpine.plugin(morph);
Alpine.plugin(collapse);

window.Alpine = Alpine;

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
