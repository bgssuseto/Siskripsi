<x-app-layout title="Kalender Akademik Dosen">
    @push('styles')
    <style>
        /* ── Calendar container ── */
        .calendar-container {
            background: #fff;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            border: 1px solid #f1f5f9;
        }
        html.dark .calendar-container {
            background: #1e293b;
            border-color: #334155;
            box-shadow: 0 1px 3px rgba(0,0,0,.3);
        }
        
        /* Customizing FullCalendar */
        .fc { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        .fc-col-header-cell-cushion { font-size: .8rem; font-weight: 700; color: #475569; text-transform: uppercase; }
        html.dark .fc-col-header-cell-cushion { color: #94a3b8 !important; }
        .fc-event { border-radius: 6px !important; padding: .1rem .3rem !important; cursor: pointer !important; font-size: .75rem !important; font-weight: 600 !important; }
        .fc-header-toolbar { margin-bottom: 1.25rem !important; }
        .fc-daygrid-day-number { font-size: .82rem !important; font-weight: 700 !important; color: #334155 !important; }
        html.dark .fc-daygrid-day-number { color: #e2e8f0 !important; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: .22rem .6rem; border-radius: 999px; font-size: .7rem; font-weight: 700; }
        .badge-sempro { background: #dbeafe; color: #1d4ed8; }
        .badge-sidang { background: #dcfce7; color: #15803d; }
        html.dark .badge-sempro { background: rgba(29,78,216,.25); color: #93c5fd; }
        html.dark .badge-sidang { background: rgba(21,128,61,.25); color: #86efac; }
        
        .nim-pill { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: #475569; padding: .15rem .5rem; border-radius: 6px; font-size: .78rem; font-weight: 600; }
        html.dark .nim-pill { background: #334155; color: #94a3b8; }

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
        <a href="{{ route('dosen.dashboard') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Dashboard
        </a>
    </div>

    @if(!$dosen)
        <div class="mb-8">
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Agenda & Kalender Ujian</h2>
            <p class="text-sm text-slate-500 mt-1">Timeline menyeluruh jadwal ujian Seminar Proposal, Sidang Skripsi, dan kegiatan akademik Anda.</p>
        </div>
        <div class="bg-white rounded-3xl border border-slate-200 p-8 text-center text-slate-500 shadow-sm">
            <h3 class="font-extrabold text-slate-800 text-base">Akun Belum Terhubung</h3>
            <p class="text-xs text-slate-400 mt-1">Hubungkan akun Anda dengan Master Dosen untuk melihat kalender agenda.</p>
        </div>
    @else
        <div class="calendar-page" x-data="{
            currentView: '{{ request('view', 'calendar') }}',
            initCalendar() {
                const calendarEl = document.getElementById('calendar-view');
                if (!calendarEl) return;
                
                const eventsData = JSON.parse(calendarEl.getAttribute('data-events') || '[]');
                const firstDate = (eventsData.length > 0 && eventsData[0].start) ? eventsData[0].start.split('T')[0] : null;

                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    initialDate: firstDate || undefined,
                    locale: 'id',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: eventsData,
                    eventDidMount: function(info) {
                        if (info.event.backgroundColor) {
                            info.el.style.setProperty('background-color', info.event.backgroundColor, 'important');
                            info.el.style.setProperty('border-color', info.event.borderColor || info.event.backgroundColor, 'important');
                            info.el.style.setProperty('color', '#ffffff', 'important');
                        }
                    },
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
                        document.getElementById('detail-jadwal').textContent = dateStr + ' | ' + props.jam;
                        document.getElementById('detail-ruang').textContent = '📍 Ruangan: ' + props.ruang;
                        
                        const jenisBadge = document.getElementById('detail-jenis');
                        jenisBadge.textContent = props.jenis;
                        jenisBadge.className = 'badge ' + (props.jenis === 'Seminar Proposal' ? 'badge-sempro' : 'badge-sidang');

                        document.getElementById('modal-detail').style.display = 'flex';
                    }
                });
                calendar.render();
            }
        }" x-init="$nextTick(() => initCalendar())">

            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Agenda & Kalender Ujian</h2>
                    <p class="text-sm text-slate-500 mt-1">Timeline menyeluruh jadwal ujian Seminar Proposal, Sidang Skripsi, dan kegiatan akademik Anda.</p>
                </div>
                
                <!-- View Toggle Buttons -->
                <div class="flex items-center gap-1.5 bg-slate-200/60 p-1.5 rounded-2xl w-fit self-start sm:self-center">
                    <button @click="currentView = 'calendar'; $nextTick(() => initCalendar())" 
                            :class="currentView === 'calendar' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Kalender View
                    </button>
                    <button @click="currentView = 'list'" 
                            :class="currentView === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Daftar Agenda
                    </button>
                </div>
            </div>

            <!-- Calendar View -->
            <div x-show="currentView === 'calendar'" id="calendar-container" class="calendar-container mb-8" x-cloak>
                <div id="calendar-view" data-events="{{ json_encode($calendarEvents) }}"></div>
            </div>

            <div x-show="currentView === 'list'" class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-cloak>
                <!-- Main Timeline (Left 2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Filters & Search Toolbar -->
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm space-y-4">
                        <form method="GET" action="{{ route('dosen.kalender') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 w-full">
                            <!-- Hidden input to keep active tab/view as list -->
                            <input type="hidden" name="view" value="list">

                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </span>
                                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama mahasiswa, NIM atau judul..." class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-800 placeholder-slate-400">
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <div>
                                    <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 font-semibold cursor-pointer">
                                </div>
                                
                                <select name="jenis" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 font-bold cursor-pointer">
                                    <option value="">Semua Jenis Ujian</option>
                                    <option value="sempro" {{ request('jenis') === 'sempro' ? 'selected' : '' }}>Seminar Proposal</option>
                                    <option value="skripsi" {{ request('jenis') === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                                </select>

                                <select name="status" class="px-3 py-2 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all text-slate-700 font-bold cursor-pointer">
                                    <option value="">Semua Status</option>
                                    <option value="terjadwal" {{ request('status') === 'terjadwal' ? 'selected' : '' }}>Terjadwal & Belum Sidang</option>
                                    <option value="proses" {{ request('status') === 'proses' ? 'selected' : '' }}>Proses Ujian</option>
                                    <option value="sudah" {{ request('status') === 'sudah' ? 'selected' : '' }}>Sudah Sidang</option>
                                </select>

                                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-855 active:bg-slate-950 text-white font-bold text-xs rounded-xl shadow-sm hover:shadow transition-all shrink-0">
                                    Filter
                                </button>

                                @if(request()->anyFilled(['search', 'tanggal', 'jenis', 'status']))
                                    <a href="{{ route('dosen.kalender', ['view' => 'list']) }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl transition-all text-center shrink-0">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                        <h3 class="font-extrabold text-slate-955 text-base mb-6 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            Daftar Agenda Terjadwal
                        </h3>

                        <div class="relative pl-6 border-l border-slate-100 space-y-8">
                            @forelse($schedules as $s)
                                @php
                                    $isSempro = $s->jenis_tugas_akhir === 'sempro';
                                    $roles = [];
                                    if($s->dosen_pembimbing_utama_id === $dosen->id) $roles[] = 'Pembimbing Utama';
                                    if($isSempro && $s->dosen_pembimbing_pendamping_id === $dosen->id) $roles[] = 'Pembimbing Pendamping';
                                    if($s->ketua_penguji_id === $dosen->id) $roles[] = 'Ketua Penguji';
                                    if($s->anggota_penguji_1_id === $dosen->id) $roles[] = 'Anggota Penguji 1';
                                    if($s->anggota_penguji_2_id === $dosen->id) $roles[] = 'Anggota Penguji 2';
                                @endphp
                                <!-- Timeline Item -->
                                <div class="relative">
                                    <!-- Dot marker -->
                                    <div class="absolute -left-[31px] top-1.5 w-4 h-4 rounded-full border-4 border-white shadow-sm transition-transform duration-300 hover:scale-125
                                        {{ $isSempro ? 'bg-blue-600' : 'bg-emerald-600' }}"></div>
                                    
                                    <div class="bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 rounded-2xl transition-all duration-200">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3.5">
                                            <div class="flex items-center gap-3">
                                                <span class="inline-flex px-2.5 py-1 text-[9px] font-extrabold rounded-lg uppercase tracking-wider
                                                    {{ $isSempro ? 'bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800' : 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800' }}">
                                                    {{ $isSempro ? 'Sempro' : 'Sidang Skripsi' }}
                                                </span>
                                                {!! $s->getJadwalStatusHtml() !!}
                                                <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span class="inline-block bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded font-extrabold">{{ $s->jam ?? '-' }}</span>
                                                </div>
                                            </div>
                                            <div class="text-xs font-bold text-slate-600 dark:text-slate-300">
                                                {{ $s->tanggal ? $s->tanggal->translatedFormat('l, d F Y') : '-' }}
                                            </div>
                                        </div>

                                        <h4 class="font-extrabold text-slate-900 dark:text-slate-100 text-base leading-snug">{{ $s->nama_mahasiswa }} <span class="text-slate-400 font-mono text-xs font-normal">({{ $s->nim }})</span></h4>
                                        <p class="text-xs text-slate-650 dark:text-slate-400 mt-1.5 leading-relaxed font-semibold">{{ $s->judul_skripsi }}</p>

                                        <div class="mt-4 pt-4 border-t border-slate-200/60 dark:border-slate-700/60 flex flex-wrap items-center justify-between gap-4">
                                            <div class="flex items-start gap-2">
                                                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-0.5 shrink-0">Peran:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    @php
                                                        $roleColors = [
                                                            'Pembimbing Utama'      => 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800',
                                                            'Pembimbing Pendamping' => 'bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800',
                                                            'Ketua Penguji'         => 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 border border-amber-200 dark:border-amber-800',
                                                            'Anggota Penguji 1'     => 'bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800',
                                                            'Anggota Penguji 2'     => 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800',
                                                        ];
                                                    @endphp
                                                    @foreach($roles as $r)
                                                        <span class="inline-block px-2 py-0.5 text-[9px] font-extrabold rounded-md {{ $roleColors[$r] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-600' }}">
                                                            {{ $r }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                </svg>
                                                Ruang: {{ $s->ruang->kode_ruangan ?? '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="py-8 text-center text-slate-500">
                                    <p class="text-sm font-medium">Tidak ada agenda ujian terjadwal.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info (Right 1 col) -->
                <div class="space-y-6">
                    <!-- Color legends -->
                    <div class="bg-white dark:bg-slate-800/80 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-sm">
                        <h3 class="font-extrabold text-slate-900 dark:text-slate-100 text-sm uppercase tracking-wider mb-4">Petunjuk Warna</h3>
                        <div class="space-y-3.5">
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-blue-600 shrink-0"></span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100">Seminar Proposal</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Ujian pemaparan proposal judul skripsi.</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="w-3.5 h-3.5 rounded-full bg-emerald-600 shrink-0"></span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:text-slate-100">Sidang Skripsi</p>
                                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Sidang pertanggungjawaban naskah skripsi akhir.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Info box -->
                    <div class="bg-gradient-to-tr from-slate-900 to-indigo-950 text-white rounded-3xl p-6 shadow-xl relative overflow-hidden">
                        <div class="absolute -right-10 -bottom-10 w-24 h-24 bg-white/5 rounded-full blur-xl"></div>
                        <h3 class="font-extrabold text-sm uppercase tracking-wider text-indigo-300 mb-3">Informasi Tambahan</h3>
                        <p class="text-xs text-slate-300 leading-relaxed font-semibold">
                            Jadwal yang tertera di atas disinkronisasi langsung oleh Koordinator Program Studi. Silakan berkoordinasi jika terdapat bentrok jadwal antar penguji.
                        </p>
                    </div>
                </div>
            </div>

            {{-- MODAL: DETAIL AGENDA --}}
            <div id="modal-detail" class="modal-overlay" style="display:none;" onclick="closeOnOverlay(event,'modal-detail')">
                <div class="modal-box">
                    <div class="modal-header bg-slate-50 border-b border-slate-100">
                        <span class="modal-title font-bold">📄 Detail Agenda Ujian</span>
                        <button class="modal-close" onclick="closeModal('modal-detail')">✕</button>
                    </div>
                    <div class="modal-body space-y-4">
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Peran Anda</span>
                            <span class="col-span-2"><span id="detail-peran" class="px-2.5 py-0.5 text-xs font-extrabold bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-lg"></span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jenis Ujian</span>
                            <span class="col-span-2"><span id="detail-jenis" class="badge"></span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">NIM / Nama</span>
                            <span class="col-span-2 text-slate-800 font-semibold"><span id="detail-nim" class="nim-pill"></span> <span id="detail-nama" class="ml-1"></span></span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Judul Skripsi</span>
                            <span class="col-span-2 text-slate-700 text-sm leading-relaxed" id="detail-judul"></span>
                        </div>
                        
                        <hr class="border-slate-100">
                        
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pembimbing</span>
                            <div class="col-span-2 space-y-1">
                                <div><span class="text-xs font-semibold text-slate-400">Utama:</span> <span id="detail-dosbing" class="text-slate-800 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400">Pendamping:</span> <span id="detail-dosbing-p" class="text-slate-800 font-medium"></span></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Penguji</span>
                            <div class="col-span-2 space-y-1">
                                <div><span class="text-xs font-semibold text-slate-400">Ketua:</span> <span id="detail-ketua" class="text-slate-800 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400">Anggota 1:</span> <span id="detail-penguji1" class="text-slate-800 font-medium"></span></div>
                                <div><span class="text-xs font-semibold text-slate-400">Anggota 2:</span> <span id="detail-penguji2" class="text-slate-800 font-medium"></span></div>
                            </div>
                        </div>
                        
                        <hr class="border-slate-100">

                        <div class="grid grid-cols-3 gap-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jadwal & Ruang</span>
                            <div class="col-span-2 space-y-1">
                                <div class="text-slate-800 font-semibold" id="detail-jadwal"></div>
                                <div><span class="schedule-ruang" id="detail-ruang"></span></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-slate-50 border-t border-slate-100">
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
