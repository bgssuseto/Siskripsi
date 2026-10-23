<x-app-layout title="Kalender Akademik Dosen">
    @push('styles')
    <style>
        .nim-pill { font-family: 'Plus Jakarta Sans', sans-serif; background: #e0f2fe; color: #0369a1; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        html.dark .nim-pill { background: rgba(14,165,233,.16); color: #7dd3fc; }

        .schedule-ruang {
            display: inline-flex; align-items: center; gap: .3rem;
            background: #fdf4ff; border: 1px solid #e9d5ff; color: #7e22ce;
            font-size: .72rem; font-weight: 700; padding: .15rem .5rem; border-radius: 6px; margin-top: .25rem;
        }
        html.dark .schedule-ruang { background: rgba(126,34,206,.2); border-color: rgba(233,213,255,.2); color: #d8b4fe; }

        /* ── Modals ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(2px);
            display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 1rem;
        }
        .modal-box {
            background: #fff; border-radius: 20px; width: 100%; max-width: 680px; max-height: 90vh;
            overflow-y: auto; box-shadow: 0 25px 60px rgba(0,0,0,.2);
        }
        html.dark .modal-box { background: #1e293b; border: 1px solid #334155; }
        .modal-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
        html.dark .modal-header { border-bottom-color: #334155; background: #0f172a; }
        .modal-title { font-size: 1.05rem; font-weight: 700; color: #0f172a; }
        html.dark .modal-title { color: #f1f5f9; }
        .modal-close {
            width: 32px; height: 32px; border: none; background: #f1f5f9; border-radius: 8px;
            cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b;
        }
        html.dark .modal-close { background: #334155; color: #94a3b8; }
        .modal-body { padding: 1.25rem 1.5rem; color: #334155; }
        html.dark .modal-body { color: #e2e8f0; }
        .modal-footer { display: flex; gap: .75rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; }
        html.dark .modal-footer { border-top-color: #334155; background: #0f172a; }

        /* Buttons */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
            padding: .6rem 1.1rem; border-radius: 12px; font-size: .82rem; font-weight: 700;
            transition: all .2s ease; cursor: pointer; border: none;
        }
        .btn-outline { background: #fff; border: 1px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
        html.dark .btn-outline { background: #1e293b; border-color: #475569; color: #94a3b8; }
        html.dark .btn-outline:hover { background: #334155; color: #e2e8f0; }
    </style>
    @endpush

    {{-- FullCalendar v6 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <!-- Back Navigation -->
    <div class="mb-4">
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    @if(!$dosen)
        <!-- Hero Banner Header -->
        <x-page-header title="Agenda & Kalender Ujian" subtitle="Timeline menyeluruh jadwal ujian Seminar Proposal, Sidang Skripsi, dan kegiatan akademik Anda." icon="calendar" accent="indigo"></x-page-header>
        <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200 dark:border-slate-700 p-8 text-center text-slate-500 dark:text-slate-400 shadow-sm">
            <h3 class="font-extrabold text-slate-800 dark:text-slate-200 text-base">Akun Belum Terhubung</h3>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Hubungkan akun Anda dengan Master Dosen untuk melihat kalender agenda.</p>
        </div>
    @else
        <div class="calendar-page" x-data="{
            currentView: '{{ request('view', 'calendar') }}',
            initCalendar() {
                const calendarEl = document.getElementById('calendar-view');
                if (!calendarEl) return;
                if (calendarEl._fc) { calendarEl._fc.updateSize(); return; }

                const eventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');
                const today = new Date().toISOString().slice(0, 10);
                const upcoming = eventsData.find(e => (e.start || '').slice(0, 10) >= today);
                const firstDate = (upcoming || eventsData[0] || {}).start;
                const isMobileScreen = window.innerWidth < 640;

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    ...CalendarKit.options(),
                    initialView: isMobileScreen ? 'listMonth' : 'dayGridMonth',
                    initialDate: firstDate ? firstDate.slice(0, 10) : undefined,
                    locale: 'id',
                    headerToolbar: isMobileScreen
                        ? { left: 'prev,next', center: 'title', right: 'today' }
                        : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
                    footerToolbar: isMobileScreen ? { center: 'listMonth,dayGridMonth' } : false,
                    slotMinTime: '07:00:00',
                    slotMaxTime: '18:00:00',
                    slotDuration: '00:30:00',
                    slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                    allDaySlot: false,
                    views: { listMonth: { buttonText: 'Daftar' }, dayGridMonth: { buttonText: 'Bulan' } },
                    events: eventsData,
                    eventContent: CalendarKit.eventContent,
                    eventDidMount: CalendarKit.eventDidMount,
                    eventClick: function(info) {
                        const props = info.event.extendedProps;

                        document.getElementById('detail-nim').textContent = props.nim;
                        document.getElementById('detail-nama').textContent = props.mahasiswa;
                        document.getElementById('detail-judul').textContent = props.judul;
                        document.getElementById('detail-dosbing').textContent = props.dosbing;
                        document.getElementById('detail-dosbing-p').textContent = props.dosbing_p;
                        document.getElementById('detail-ketua').textContent = props.ketua_penguji;
                        document.getElementById('detail-penguji1').textContent = props.penguji_1;
                        document.getElementById('detail-penguji2').textContent = props.penguji_2;
                        document.getElementById('detail-peran').textContent = props.role;

                        const dateStr = info.event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        document.getElementById('detail-jadwal').textContent = dateStr + ' · ' + props.jam;
                        document.getElementById('detail-ruang').textContent = 'Ruang ' + props.ruang;

                        const jenisBadge = document.getElementById('detail-jenis');
                        jenisBadge.textContent = props.jenis;
                        jenisBadge.className = 'cal-badge cal-badge-' + CalendarKit.jenisKey(props);

                        document.getElementById('modal-detail').style.display = 'flex';
                    }
                });
                calendar.render();
                calendarEl._fc = CalendarKit.register(calendar);
            }
        }" x-init="$nextTick(() => initCalendar())">

            <!-- Header Section -->
            <x-page-header title="Agenda & Kalender Ujian" subtitle="Timeline menyeluruh jadwal ujian Seminar Proposal, Sidang Skripsi, dan kegiatan akademik Anda." icon="calendar" accent="indigo"></x-page-header>

            <!-- View Toggle Buttons -->
            <div class="flex sm:justify-end mb-6">
                <div class="grid grid-cols-2 sm:flex items-center gap-1 bg-slate-200/70 dark:bg-slate-800 p-1 rounded-2xl w-full sm:w-fit ring-1 ring-inset ring-slate-200 dark:ring-slate-700">
                    <button @click="currentView = 'calendar'; $nextTick(() => initCalendar())"
                            :class="currentView === 'calendar' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 rounded-xl text-xs font-bold transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Kalender View
                    </button>
                    <button @click="currentView = 'list'"
                            :class="currentView === 'list' ? 'bg-white dark:bg-slate-700 text-indigo-600 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 rounded-xl text-xs font-bold transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Daftar Agenda
                    </button>
                </div>
            </div>

            <!-- Calendar View -->
            <div x-show="currentView === 'calendar'" id="calendar-container" class="mb-8" x-cloak>
                <x-calendar-frame title="Kalender Ujian Saya" subtitle="Seluruh jadwal ujian yang Anda bimbing atau uji. Klik jadwal untuk melihat detail.">
                    <div id="calendar-view" data-events="{{ json_encode($calendarEvents) }}"></div>
                </x-calendar-frame>
            </div>

            <div x-show="currentView === 'list'" class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-8" x-cloak>
                <!-- Main Timeline (Left 2 cols) -->
                <div class="lg:col-span-2 space-y-5 lg:space-y-6 min-w-0">
                    <!-- Filters & Search Toolbar -->
                    <div class="bg-white dark:bg-slate-800/80 p-4 sm:p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm">
                        <form method="GET" action="{{ route('dosen.kalender') }}" class="flex flex-col gap-3 w-full">
                            <!-- Hidden input to keep active tab/view as list -->
                            <input type="hidden" name="view" value="list">

                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama mahasiswa, NIM atau judul..." class="w-full pl-10 pr-4 py-2.5 border border-slate-200 dark:border-slate-600 rounded-xl text-sm bg-white dark:bg-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-800 dark:text-slate-200 placeholder-slate-400 dark:placeholder-slate-500">
                            </div>

                            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2 sm:gap-3">
                                <div class="col-span-2 sm:col-span-1">
                                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="w-full px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 dark:text-slate-200 font-semibold cursor-pointer">
                                </div>

                                <select name="jenis" class="w-full sm:w-auto min-w-0 px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 dark:text-slate-200 font-bold cursor-pointer">
                                    <option value="">Semua Jenis Ujian</option>
                                    <option value="sempro" {{ request('jenis') === 'sempro' ? 'selected' : '' }}>Seminar Proposal</option>
                                    <option value="skripsi" {{ request('jenis') === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                                </select>

                                <select name="status" class="w-full sm:w-auto min-w-0 px-3 py-2 border border-slate-200 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 dark:text-slate-200 font-bold cursor-pointer">
                                    <option value="">Akan Datang (Default)</option>
                                    <option value="terjadwal" {{ request('status') === 'terjadwal' ? 'selected' : '' }}>Terjadwal & Belum Sidang</option>
                                    <option value="proses" {{ request('status') === 'proses' ? 'selected' : '' }}>Proses Ujian</option>
                                    <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Sidang</option>
                                </select>

                                <div class="col-span-2 sm:col-span-1 flex gap-2">
                                    <button type="submit" class="flex-1 sm:flex-none px-4 py-2 bg-slate-900 dark:bg-indigo-600 hover:bg-slate-800 dark:hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all cursor-pointer">
                                        Filter
                                    </button>

                                    @if(request()->anyFilled(['search', 'tanggal', 'jenis', 'status']))
                                        <a href="{{ route('dosen.kalender', ['view' => 'list']) }}" class="flex-1 sm:flex-none px-4 py-2 border border-slate-200 dark:border-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl transition-all text-center">
                                            Reset
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                    @php
                        $agendaSempro = $schedules->where('jenis_tugas_akhir', 'sempro')->values();
                        $agendaSkripsi = $schedules->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->values();
                    @endphp

                    <!-- Kategori: Seminar Proposal -->
                    <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-4 sm:p-6 shadow-sm" x-data="{ open: true }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 text-left cursor-pointer">
                            <h3 class="font-extrabold text-slate-900 dark:text-slate-100 text-sm sm:text-base flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
                                Agenda Seminar Proposal
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200">{{ $agendaSempro->count() }}</span>
                            </h3>
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="relative sm:pl-6 sm:border-l border-slate-200 dark:border-slate-700 space-y-3 sm:space-y-5 mt-4 sm:mt-6">
                            @forelse($agendaSempro as $s)
                                @include('dosen.partials._agenda-item', ['s' => $s, 'dosen' => $dosen])
                            @empty
                                <div class="py-8 text-center text-slate-500 dark:text-slate-400">
                                    <p class="text-sm font-medium">Tidak ada agenda seminar proposal terjadwal.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Kategori: Sidang Skripsi -->
                    <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-4 sm:p-6 shadow-sm" x-data="{ open: true }">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between gap-2 text-left cursor-pointer">
                            <h3 class="font-extrabold text-slate-900 dark:text-slate-100 text-sm sm:text-base flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 shrink-0"></span>
                                Agenda Sidang Skripsi
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200">{{ $agendaSkripsi->count() }}</span>
                            </h3>
                            <svg class="w-5 h-5 text-slate-400 dark:text-slate-500 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="relative sm:pl-6 sm:border-l border-slate-200 dark:border-slate-700 space-y-3 sm:space-y-5 mt-4 sm:mt-6">
                            @forelse($agendaSkripsi as $s)
                                @include('dosen.partials._agenda-item', ['s' => $s, 'dosen' => $dosen])
                            @empty
                                <div class="py-8 text-center text-slate-500 dark:text-slate-400">
                                    <p class="text-sm font-medium">Tidak ada agenda sidang skripsi terjadwal.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info (Right 1 col) -->
                <div class="space-y-6">
                    <!-- Color legends -->
                    <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-5 sm:p-6 shadow-sm">
                        <h3 class="font-extrabold text-slate-900 dark:text-slate-100 text-sm uppercase tracking-wider mb-4">Petunjuk Warna</h3>
                        <div class="space-y-3.5">
                            @foreach([['sempro', 'Seminar Proposal', 'Ujian pemaparan proposal skripsi.'], ['skripsi', 'Sidang Skripsi', 'Sidang naskah skripsi akhir.'], ['jurnal', 'Sidang Jurnal', 'Sidang jalur publikasi jurnal.']] as [$key, $label, $desc])
                                <div class="flex items-start gap-3">
                                    <span class="cal-badge cal-badge-{{ $key }} mt-0.5">{{ $label }}</span>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-snug">{{ $desc }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Academic Info box -->
                    <div class="bg-indigo-50/60 dark:bg-indigo-500/10 border border-indigo-100 dark:border-indigo-500/20 rounded-3xl p-5 sm:p-6">
                        <h3 class="flex items-center gap-2 font-extrabold text-sm text-indigo-900 dark:text-indigo-200 mb-2"><x-icon name="info" class="w-4 h-4" /> Informasi Tambahan</h3>
                        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                            Jadwal yang tertera di atas disinkronisasi langsung oleh Koordinator Program Studi. Silakan berkoordinasi jika terdapat bentrok jadwal antar penguji.
                        </p>
                    </div>
                </div>
            </div>

            {{-- MODAL: DETAIL AGENDA --}}
            <div id="modal-detail" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-detail')">
                <div class="modal-box">
                    <div class="modal-header bg-slate-50 dark:bg-slate-800 border-b border-slate-100 dark:border-slate-700">
                        <span class="modal-title font-bold"><x-icon name="document" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Detail Agenda Ujian</span>
                        <button class="modal-close" onclick="closeModal('modal-detail')"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
                    </div>
                    <div class="modal-body space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Peran Anda</span>
                            <span class="col-span-2"><span id="detail-peran" class="cal-badge cal-badge-role no-dot"></span></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jenis Ujian</span>
                            <span class="col-span-2"><span id="detail-jenis" class="cal-badge"></span></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">NIM / Nama</span>
                            <span class="col-span-2 text-slate-800 dark:text-slate-100 font-semibold"><span id="detail-nim" class="nim-pill"></span> <span id="detail-nama" class="ml-1"></span></span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Judul Skripsi</span>
                            <span class="col-span-2 text-slate-700 dark:text-slate-300 text-sm leading-relaxed" id="detail-judul"></span>
                        </div>

                        <hr class="border-slate-100 dark:border-slate-700">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Pembimbing</span>
                            <div class="col-span-2 space-y-1">
                                <div><span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Utama:</span> <span id="detail-dosbing" class="text-slate-800 dark:text-slate-200 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Pendamping:</span> <span id="detail-dosbing-p" class="text-slate-800 dark:text-slate-200 font-medium"></span></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Penguji</span>
                            <div class="col-span-2 space-y-1">
                                <div><span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Ketua:</span> <span id="detail-ketua" class="text-slate-800 dark:text-slate-200 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Anggota 1:</span> <span id="detail-penguji1" class="text-slate-800 dark:text-slate-200 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400 dark:text-slate-500">Anggota 2:</span> <span id="detail-penguji2" class="text-slate-800 dark:text-slate-200 font-medium"></span></div>
                            </div>
                        </div>

                        <hr class="border-slate-100 dark:border-slate-700">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-1 sm:gap-2">
                            <span class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">Jadwal & Ruang</span>
                            <div class="col-span-2 space-y-1">
                                <div class="text-slate-800 dark:text-slate-200 font-semibold" id="detail-jadwal"></div>
                                <div><span class="schedule-ruang" id="detail-ruang"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-slate-50 dark:bg-slate-800 border-t border-slate-100 dark:border-slate-700">
                        <button type="button" class="btn btn-outline" onclick="closeModal('modal-detail')">Tutup</button>
                    </div>
                </div>
            </div>

        </div>
    @endif

    <script>
        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }
        function closeOnOverlay(event, id) {
            if (event.target.id === id) {
                closeModal(id);
            }
        }
    </script>
</x-app-layout>
