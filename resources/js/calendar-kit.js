/**
 * CalendarKit — tampilan event FullCalendar yang seragam di seluruh aplikasi
 * (Kalender Dosen, Jadwal Sempro, Jadwal Skripsi) + dukungan mode "Perbesar"
 * dari komponen <x-calendar-frame>. Gaya visual: resources/css/calendar.css.
 *
 * Pemakaian di halaman:
 *   new FullCalendar.Calendar(el, { ...CalendarKit.options(), eventContent: CalendarKit.eventContent,
 *                                   eventDidMount: CalendarKit.eventDidMount, ... });
 *   CalendarKit.register(calendar);
 */

const JENIS = {
    sempro:  { label: 'Sempro',  cls: 'cal-badge-sempro',  color: '#6366f1' },
    skripsi: { label: 'Skripsi', cls: 'cal-badge-skripsi', color: '#10b981' },
    jurnal:  { label: 'Jurnal',  cls: 'cal-badge-jurnal',  color: '#f59e0b' },
};

function jenisKey(props = {}, title = '') {
    const s = `${props.jenis || ''} ${title}`.toLowerCase();
    if (s.includes('proposal') || s.includes('sempro')) return 'sempro';
    if (s.includes('jurnal')) return 'jurnal';
    return 'skripsi';
}

function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined && text !== null) n.textContent = text;
    return n;
}

/** Nama bersih: buang awalan ⚠️ dan akhiran "(Sempro)"/"(Skripsi)" — sudah diwakili badge. */
function cleanTitle(title = '') {
    return title.replace(/^\s*⚠️?\s*/u, '').replace(/\s*\((Sempro|Skripsi|Sidang|Jurnal)[^)]*\)\s*$/i, '').trim();
}

function badge(key, xs = false) {
    const j = JENIS[key];
    return el('span', `cal-badge ${j.cls}${xs ? ' cal-badge-xs' : ''}`, j.label);
}

const CalendarKit = {
    jenisKey,

    /** Opsi dasar yang sama untuk semua kalender. */
    options() {
        return {
            eventDisplay: 'block',
            dayMaxEventRows: 3,
            nowIndicator: true,
            height: 'auto',
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            buttonText: { today: 'Hari ini', month: 'Bulan', week: 'Minggu', day: 'Hari', list: 'Daftar' },
            moreLinkText: (n) => `+${n} lainnya`,
            noEventsText: 'Tidak ada jadwal pada rentang ini.',
        };
    },

    eventContent(arg) {
        const props = arg.event.extendedProps || {};
        const key = jenisKey(props, arg.event.title);
        const jam = props.jam && props.jam !== '-' ? props.jam : '';
        const ruang = props.ruang && props.ruang !== 'TBA' ? props.ruang : '';
        const isList = arg.view.type.startsWith('list');
        const wrap = el('div', 'cal-ev-body');

        const top = el('div', 'cal-ev-top');
        top.appendChild(badge(key, !isList));
        if (props.has_conflict) {
            top.appendChild(el('span', `cal-badge cal-badge-conflict${isList ? '' : ' cal-badge-xs'}`, 'Bentrok'));
        }
        if (!isList && jam) {
            // Di tampilan Bulan sel sempit: cukup jam mulai. Minggu/Hari: rentang penuh.
            const waktu = arg.view.type.startsWith('dayGrid') ? jam.split('-')[0].trim() : jam.replace(/\s+/g, '');
            top.appendChild(el('span', 'cal-ev-time', waktu));
        }
        if (isList && props.role) top.appendChild(el('span', 'cal-badge cal-badge-role no-dot', props.role));
        wrap.appendChild(top);

        wrap.appendChild(el('div', 'cal-ev-title', cleanTitle(arg.event.title)));

        const meta = isList
            ? [props.nim, ruang ? `Ruang ${ruang}` : '', props.judul].filter(Boolean).join(' · ')
            : (ruang ? `Ruang ${ruang}` : '');
        if (meta) wrap.appendChild(el('div', 'cal-ev-meta', meta));

        return { domNodes: [wrap] };
    },

    eventDidMount(info) {
        const props = info.event.extendedProps || {};
        const key = jenisKey(props, info.event.title);
        info.el.classList.add('cal-ev');
        if (props.has_conflict) info.el.classList.add('cal-ev-conflict');
        info.el.style.setProperty('--ev', props.has_conflict ? '#e11d48' : JENIS[key].color);
        info.el.style.removeProperty('background-color');
        info.el.style.removeProperty('border-color');

        const tip = [cleanTitle(info.event.title), props.nim, props.jam, props.ruang ? `Ruang ${props.ruang}` : '']
            .filter((v) => v && v !== '-' && v !== 'TBA');
        if (props.conflict_notes) tip.push(`⚠ ${props.conflict_notes}`);
        info.el.title = tip.join(' • ');
    },

    /** Daftarkan kalender agar ikut menyesuaikan tinggi saat mode Perbesar. */
    register(calendar) {
        if (!calendar || calendar.__calendarKit) return calendar;
        calendar.__calendarKit = true;
        window.addEventListener('calendar-frame:toggle', (e) => {
            const frame = e.detail?.frame;
            if (!frame || !calendar.el || !frame.contains(calendar.el)) return;
            calendar.setOption('height', e.detail.expanded ? '100%' : 'auto');
            // Hitung ulang ukuran setelah tata letak & animasi perbesar selesai.
            requestAnimationFrame(() => calendar.updateSize());
            setTimeout(() => { calendar.updateSize(); window.dispatchEvent(new Event('resize')); }, 260);
        });
        return calendar;
    },
};

window.CalendarKit = CalendarKit;
export default CalendarKit;
