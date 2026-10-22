<x-app-layout title="Manajemen Menu">
    <x-slot:header>Manajemen Menu &amp; Hak Akses</x-slot:header>

    @php
        $ikonSvg = collect($daftarIkon)->mapWithKeys(fn ($n) => [$n => \Illuminate\Support\Facades\Blade::render('<x-icon :name="$n" class="w-4 h-4" />', ['n' => $n])]);
        $ikonSvg['_default'] = \Illuminate\Support\Facades\Blade::render('<x-icon name="list" class="w-4 h-4" />');
        $ikonSvg['badge'] = $ikonSvg['badge-check'];
        $konfigurasi = [
            'menus'    => $menus->map(fn ($m) => \App\Http\Controllers\MenuController::menuJson($m))->values(),
            'roleMenu' => $roleMenu,
            'ikon'     => $ikonSvg,
            'url'      => [
                'menu'    => url('/kelola-menu'),
                'urutan'  => route('admin.menus.reorder'),
                'users'   => route('admin.menus.users'),
                'role'    => url('/kelola-menu/role'),
                'user'    => url('/kelola-menu/user'),
            ],
            'csrf'     => csrf_token(),
        ];
    @endphp

    <script>
        window.manajemenMenu = function (cfg) {
            const KELOMPOK = {
                koordinator: { label: 'Menu Koordinator', warna: 'sky' },
                dosen:       { label: 'Menu Dosen', warna: 'amber' },
                mahasiswa:   { label: 'Menu Mahasiswa', warna: 'emerald' },
                super_admin: { label: 'Menu Super Admin', warna: 'violet' },
                all:         { label: 'Menu Umum (Semua Role)', warna: 'slate' },
            };
            const ROLE = {
                koordinator: { label: 'Koordinator', ket: 'Koordinator skripsi / admin prodi' },
                dosen:       { label: 'Dosen', ket: 'Dosen pembimbing & penguji' },
                mahasiswa:   { label: 'Mahasiswa', ket: 'Mahasiswa pendaftar sempro & skripsi' },
            };

            return {
                tab: (() => { try { return localStorage.getItem('menu-tab') || 'role'; } catch (e) { return 'role'; } })(),
                menus: cfg.menus,
                roleMenu: Object.fromEntries(Object.entries(cfg.roleMenu).map(([r, ids]) => [r, [...ids]])),
                roleAwal: Object.fromEntries(Object.entries(cfg.roleMenu).map(([r, ids]) => [r, [...ids]])),
                KELOMPOK, ROLE,
                role: 'koordinator',
                cari: '',
                tertutup: {},
                menyimpan: false,

                // Form menu (tambah / ubah)
                formTerbuka: false,
                form: {},
                galat: {},
                hapusTerbuka: false,
                akanDihapus: null,

                // Tab user
                users: [], userQ: '', userRole: '', userKhusus: false, userHalaman: 1, userTerakhir: 1, userTotal: 0, userMemuat: false,
                userTerpilih: null, userMenu: [], userTerbuka: false,

                init() {
                    this.$watch('tab', (t) => { try { localStorage.setItem('menu-tab', t); } catch (e) {} if (t === 'user' && !this.users.length) this.muatUser(); });
                    if (this.tab === 'user') this.muatUser();
                    window.addEventListener('beforeunload', (e) => { if (this.jumlahPerubahan()) { e.preventDefault(); e.returnValue = ''; } });
                },

                ikon(nama) { return cfg.ikon[nama] || cfg.ikon._default; },
                beri(pesan, tipe = 'success') { window.dispatchEvent(new CustomEvent('notify', { detail: { message: pesan, type: tipe } })); },

                async kirim(url, method = 'POST', body = null) {
                    const res = await fetch(url, {
                        method,
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                        body: body ? JSON.stringify(body) : null,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { const err = new Error(data.message || 'Terjadi kesalahan.'); err.status = res.status; err.data = data; throw err; }
                    return data;
                },

                // ── Pohon menu ──
                urut(a, b) { return (a.sort_order - b.sort_order) || (a.id - b.id); },
                induk(kel) { return this.menus.filter(m => !m.parent_id && (m.role_default || 'all') === kel).sort(this.urut); },
                anak(id) { return this.menus.filter(m => m.parent_id === id).sort(this.urut); },
                cocok(m) {
                    const q = this.cari.trim().toLowerCase();
                    if (!q) return true;
                    const teks = (m.name + ' ' + (m.route || '')).toLowerCase();
                    return teks.includes(q) || this.anak(m.id).some(c => (c.name + ' ' + (c.route || '')).toLowerCase().includes(q));
                },
                kelompokTerurut() {
                    const urutan = ['koordinator', 'dosen', 'mahasiswa', 'super_admin', 'all'];
                    if (this.tab === 'role') urutan.sort((a, b) => (b === this.role) - (a === this.role));
                    return urutan.filter(k => this.induk(k).length);
                },
                jumlahKelompok(kel) { return this.menus.filter(m => (m.role_default || 'all') === kel).length; },

                // ── Hak akses role ──
                punya(id) { return this.roleMenu[this.role].includes(id); },
                setel(id, nyala) {
                    const daftar = this.roleMenu[this.role];
                    const ada = daftar.includes(id);
                    if (nyala && !ada) daftar.push(id);
                    if (!nyala && ada) daftar.splice(daftar.indexOf(id), 1);
                },
                status(m) {
                    const anak = this.anak(m.id);
                    if (!anak.length) return this.punya(m.id) ? 'penuh' : 'kosong';
                    const n = anak.filter(c => this.punya(c.id)).length;
                    if (n === anak.length && (this.punya(m.id) || !m.route)) return 'penuh';
                    return (n || this.punya(m.id)) ? 'sebagian' : 'kosong';
                },
                alih(m) {
                    const nyala = this.status(m) !== 'penuh';
                    this.setel(m.id, nyala);
                    this.anak(m.id).forEach(c => this.setel(c.id, nyala));
                },
                alihAnak(c) {
                    const nyala = !this.punya(c.id);
                    this.setel(c.id, nyala);
                    if (nyala) this.setel(c.parent_id, true); // induk ikut aktif supaya sub-menu tampil
                },
                semuaKelompok(kel, nyala) {
                    this.menus.filter(m => (m.role_default || 'all') === kel).forEach(m => this.setel(m.id, nyala));
                },
                jumlahAkses(role) { return this.roleMenu[role].filter(id => this.menus.some(m => m.id === id && m.route)).length; },
                perubahan(role) {
                    const a = new Set(this.roleAwal[role]), b = new Set(this.roleMenu[role]);
                    return [...b].filter(x => !a.has(x)).length + [...a].filter(x => !b.has(x)).length;
                },
                jumlahPerubahan() { return Object.keys(this.ROLE).reduce((n, r) => n + this.perubahan(r), 0); },
                batalkan() { for (const r of Object.keys(this.ROLE)) this.roleMenu[r] = [...this.roleAwal[r]]; },
                async simpanRole() {
                    this.menyimpan = true;
                    try {
                        for (const r of Object.keys(this.ROLE).filter(r => this.perubahan(r))) {
                            const data = await this.kirim(`${cfg.url.role}/${r}`, 'POST', { menu_ids: this.roleMenu[r] });
                            this.roleAwal[r] = [...this.roleMenu[r]];
                            this.beri(data.message);
                        }
                    } catch (e) { this.beri(e.message, 'error'); }
                    finally { this.menyimpan = false; }
                },
                aksesRole(id) { return Object.keys(this.ROLE).filter(r => this.roleAwal[r].includes(id)); },

                // ── Struktur menu ──
                async alihAktif(m) {
                    const lama = m.is_active; m.is_active = !lama;
                    try { const d = await this.kirim(`${cfg.url.menu}/${m.hid}/toggle`); this.beri(d.message); }
                    catch (e) { m.is_active = lama; this.beri(e.message, 'error'); }
                },
                async geser(m, arah) {
                    const saudara = m.parent_id ? this.anak(m.parent_id) : this.induk(m.role_default || 'all');
                    const i = saudara.findIndex(x => x.id === m.id), j = i + arah;
                    if (j < 0 || j >= saudara.length) return;
                    [saudara[i], saudara[j]] = [saudara[j], saudara[i]];
                    saudara.forEach((x, k) => x.sort_order = k + 1);
                    try { await this.kirim(cfg.url.urutan, 'POST', { ids: saudara.map(x => x.id) }); }
                    catch (e) { this.beri(e.message, 'error'); }
                },
                bukaForm(m = null, induk = null) {
                    this.galat = {};
                    this.form = m
                        ? { ...m, parent_id: m.parent_id ?? '' }
                        : { hid: null, name: '', route: '', icon: 'list', role_default: induk?.role_default || (this.tab === 'role' ? this.role : 'koordinator'), parent_id: induk?.id ?? '', is_active: true };
                    this.formTerbuka = true;
                    this.$nextTick(() => this.$refs.namaMenu?.focus());
                },
                indukPilihan() {
                    return this.menus.filter(m => !m.parent_id && (m.role_default || 'all') === this.form.role_default && m.id !== this.form.id).sort(this.urut);
                },
                async simpanMenu() {
                    this.menyimpan = true; this.galat = {};
                    const body = {
                        name: this.form.name, route: this.form.route || null, icon: this.form.icon || null,
                        role_default: this.form.role_default, parent_id: this.form.parent_id ? Number(this.form.parent_id) : null,
                        is_active: this.form.is_active ? 1 : 0,
                    };
                    try {
                        const d = this.form.hid
                            ? await this.kirim(`${cfg.url.menu}/${this.form.hid}`, 'PUT', body)
                            : await this.kirim(cfg.url.menu, 'POST', body);
                        const i = this.menus.findIndex(x => x.id === d.menu.id);
                        if (i >= 0) this.menus.splice(i, 1, d.menu); else this.menus.push(d.menu);
                        this.formTerbuka = false;
                        this.beri(d.message);
                    } catch (e) {
                        if (e.status === 422 && e.data?.errors) this.galat = e.data.errors; else this.beri(e.message, 'error');
                    } finally { this.menyimpan = false; }
                },
                konfirmasiHapus(m) { this.akanDihapus = m; this.hapusTerbuka = true; },
                async hapus() {
                    const m = this.akanDihapus; this.menyimpan = true;
                    try {
                        const d = await this.kirim(`${cfg.url.menu}/${m.hid}`, 'DELETE');
                        this.menus = this.menus.filter(x => x.id !== m.id);
                        for (const r of Object.keys(this.ROLE)) { this.roleMenu[r] = this.roleMenu[r].filter(x => x !== m.id); this.roleAwal[r] = this.roleAwal[r].filter(x => x !== m.id); }
                        this.hapusTerbuka = false;
                        this.beri(d.message);
                    } catch (e) { this.beri(e.message, 'error'); }
                    finally { this.menyimpan = false; }
                },

                // ── Akses khusus user ──
                async muatUser(halaman = 1) {
                    this.userMemuat = true;
                    const p = new URLSearchParams({ q: this.userQ, role: this.userRole, page: halaman });
                    if (this.userKhusus) p.set('khusus', 1);
                    try {
                        const res = await fetch(`${cfg.url.users}?${p}`, { headers: { 'Accept': 'application/json' } });
                        const d = await res.json();
                        this.users = d.data; this.userHalaman = d.halaman; this.userTerakhir = d.terakhir; this.userTotal = d.total;
                    } catch (e) { this.beri('Gagal memuat daftar user.', 'error'); }
                    finally { this.userMemuat = false; }
                },
                dariRoleUser(id) {
                    if (!this.userTerpilih) return false;
                    if (this.userTerpilih.roles.includes('super_admin')) return true;
                    return this.userTerpilih.roles.some(r => (this.roleAwal[r] || []).includes(id));
                },
                bukaUser(u) { this.userTerpilih = u; this.userMenu = [...u.custom]; this.cari = ''; this.userTerbuka = true; },
                alihUser(id) { const i = this.userMenu.indexOf(id); i >= 0 ? this.userMenu.splice(i, 1) : this.userMenu.push(id); },
                async simpanUser() {
                    this.menyimpan = true;
                    try {
                        const d = await this.kirim(`${cfg.url.user}/${this.userTerpilih.hid}`, 'POST', { menu_ids: this.userMenu });
                        this.userTerpilih.custom = d.custom;
                        this.userTerbuka = false;
                        this.beri(d.message);
                    } catch (e) { this.beri(e.message, 'error'); }
                    finally { this.menyimpan = false; }
                },
                namaMenu(id) { return this.menus.find(m => m.id === id)?.name ?? '—'; },
            };
        };
    </script>

    <div x-data="manajemenMenu(@js($konfigurasi))" class="space-y-6 pb-24">

        <x-page-header title="Manajemen Menu & Hak Akses" icon="cog" accent="violet">
            <x-slot:description>Atur menu apa saja yang tampil untuk setiap role, susun struktur menu sidebar, dan beri akses tambahan ke user tertentu — semua tersimpan tanpa memuat ulang halaman.</x-slot:description>
            <x-slot:actions>
                <button type="button" @click="bukaForm()" class="ui-btn-primary"><x-icon name="plus" class="w-4 h-4" /> Tambah Menu</button>
            </x-slot:actions>
        </x-page-header>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            @foreach([
                ['Total Menu', $statistik['total'], 'list', 'violet'],
                ['Menu Aktif', $statistik['aktif'], 'check-circle', 'green'],
                ['Role Dikelola', count(\App\Http\Controllers\MenuController::ROLE_DIATUR), 'users', 'blue'],
                ['User Akses Khusus', $statistik['userKhusus'], 'key', 'amber'],
            ] as [$label, $nilai, $ik, $warna])
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 p-4 flex items-center gap-3 shadow-sm">
                    <span class="stat-icon {{ $warna }} w-10 h-10 rounded-xl flex items-center justify-center shrink-0"><x-icon :name="$ik" class="w-5 h-5" /></span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 truncate">{{ $label }}</p>
                        <p class="text-xl font-extrabold text-slate-900 dark:text-slate-100 leading-tight">{{ $nilai }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Tab --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 overflow-x-auto max-w-full">
                @foreach(['role' => ['Hak Akses Role', 'shield'], 'struktur' => ['Struktur Menu', 'list'], 'user' => ['Akses Khusus User', 'user']] as $kunci => [$label, $ik])
                    <button type="button" @click="tab = '{{ $kunci }}'"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-all"
                            :class="tab === '{{ $kunci }}' ? 'bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                        <x-icon :name="$ik" class="w-4 h-4" /> {{ $label }}
                        @if($kunci === 'role')
                            <span x-show="jumlahPerubahan()" x-cloak class="ml-0.5 min-w-[1.25rem] h-5 px-1 rounded-full bg-amber-500 text-white text-[10px] flex items-center justify-center" x-text="jumlahPerubahan()"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div class="relative w-full md:w-72" x-show="tab !== 'user'">
                <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                <input type="search" x-model="cari" placeholder="Cari nama menu atau route…"
                       class="w-full pl-9 pr-3 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        {{-- ═════════════ TAB 1: HAK AKSES ROLE ═════════════ --}}
        <section x-show="tab === 'role'" class="grid grid-cols-1 lg:grid-cols-[17rem_minmax(0,1fr)] gap-5 items-start">
            {{-- Pilih role --}}
            <div class="grid grid-cols-3 lg:grid-cols-1 gap-2 lg:gap-2.5 lg:sticky lg:top-24">
                <p class="col-span-3 lg:col-span-1 px-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pilih role</p>
                <template x-for="(info, r) in ROLE" :key="r">
                    <button type="button" @click="role = r"
                            class="w-full text-left p-2.5 lg:p-3.5 rounded-2xl border transition-all flex flex-col lg:flex-row items-center lg:items-center gap-1.5 lg:gap-3 text-center lg:text-left"
                            :class="role === r ? 'bg-indigo-50 dark:bg-indigo-500/10 border-indigo-300 dark:border-indigo-500/40 ring-2 ring-indigo-500/15' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-sm font-extrabold shrink-0"
                              :class="{ 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300': r === 'koordinator', 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300': r === 'dosen', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300': r === 'mahasiswa' }"
                              x-text="info.label.charAt(0)"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100" x-text="info.label"></span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400 truncate"><span x-text="jumlahAkses(r)"></span><span class="hidden lg:inline"> halaman dapat dibuka</span><span class="lg:hidden"> menu</span></span>
                        </span>
                        <span x-show="perubahan(r)" x-cloak class="hidden lg:block w-2 h-2 rounded-full bg-amber-500 shrink-0" title="Ada perubahan belum disimpan"></span>
                    </button>
                </template>
                <div class="hidden lg:flex p-3.5 rounded-2xl border border-dashed border-violet-200 dark:border-violet-500/30 bg-violet-50/60 dark:bg-violet-500/5 items-center gap-3">
                    <span class="w-10 h-10 rounded-xl flex items-center justify-center bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 shrink-0"><x-icon name="shield" class="w-5 h-5" /></span>
                    <span class="min-w-0">
                        <span class="block text-sm font-extrabold text-slate-900 dark:text-slate-100">Super Admin</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Selalu bisa membuka semua menu</span>
                    </span>
                </div>
            </div>

            {{-- Daftar menu dengan saklar --}}
            <div class="space-y-4 min-w-0">
                <div class="flex items-start gap-2.5 p-3.5 rounded-2xl bg-sky-50 dark:bg-sky-500/10 border border-sky-200 dark:border-sky-500/30 text-xs text-sky-800 dark:text-sky-200">
                    <x-icon name="info" class="w-4 h-4 shrink-0 mt-px" />
                    <p>Nyalakan saklar untuk memberi akses role <strong x-text="ROLE[role].label"></strong>. Saklar induk menyalakan/mematikan semua sub-menunya sekaligus. Perubahan baru berlaku setelah menekan <strong>Simpan</strong>.</p>
                </div>

                <template x-for="kel in kelompokTerurut()" :key="kel">
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden" x-show="induk(kel).some(m => cocok(m))">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                            <button type="button" @click="tertutup[kel] = !tertutup[kel]" class="flex items-center gap-2 min-w-0 text-left">
                                <svg class="w-4 h-4 text-slate-400 transition-transform shrink-0" :class="tertutup[kel] ? '-rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                <span class="text-sm font-extrabold text-slate-800 dark:text-slate-100 truncate" x-text="KELOMPOK[kel].label"></span>
                                <span x-show="kel === role" class="hidden sm:inline px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">Kelompok role ini</span>
                            </button>
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" @click="semuaKelompok(kel, true)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10">Pilih semua</button>
                                <button type="button" @click="semuaKelompok(kel, false)" class="px-2.5 py-1 rounded-lg text-[11px] font-bold text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Kosongkan</button>
                            </div>
                        </div>

                        <ul x-show="!tertutup[kel]" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <template x-for="m in induk(kel).filter(x => cocok(x))" :key="m.id">
                                <li>
                                    <div class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                        <span class="w-8 h-8 rounded-lg flex items-center justify-center bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 shrink-0" x-html="ikon(m.icon)"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate">
                                                <span x-text="m.name"></span>
                                                <span x-show="!m.is_active" class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">Nonaktif</span>
                                            </p>
                                            <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono truncate" x-text="m.route || (anak(m.id).length + ' sub-menu')"></p>
                                        </div>
                                        <span x-show="status(m) === 'sebagian'" class="hidden sm:inline px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Sebagian</span>
                                        <button type="button" role="switch" :aria-checked="status(m) === 'penuh'" @click="alih(m)"
                                                class="relative w-11 h-6 rounded-full transition-colors shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                :class="status(m) === 'penuh' ? 'bg-indigo-600' : (status(m) === 'sebagian' ? 'bg-indigo-300 dark:bg-indigo-500/50' : 'bg-slate-200 dark:bg-slate-700')">
                                            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full shadow transition-transform" style="background-color:#fff" :class="status(m) === 'penuh' ? 'translate-x-5' : (status(m) === 'sebagian' ? 'translate-x-2.5' : '')"></span>
                                        </button>
                                    </div>
                                    <ul x-show="anak(m.id).length" class="pb-1.5">
                                        <template x-for="c in anak(m.id)" :key="c.id">
                                            <li class="flex items-center gap-3 pl-8 sm:pl-12 pr-4 py-2 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                                <span class="w-3 h-px bg-slate-300 dark:bg-slate-600 shrink-0"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-[13px] font-semibold text-slate-700 dark:text-slate-200 truncate">
                                                        <span x-text="c.name"></span>
                                                        <span x-show="!c.is_active" class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300">Nonaktif</span>
                                                    </p>
                                                    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-mono truncate" x-text="c.route || '—'"></p>
                                                </div>
                                                <button type="button" role="switch" :aria-checked="punya(c.id)" @click="alihAnak(c)"
                                                        class="relative w-10 h-[22px] rounded-full transition-colors shrink-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                        :class="punya(c.id) ? 'bg-indigo-600' : 'bg-slate-200 dark:bg-slate-700'">
                                                    <span class="absolute top-0.5 left-0.5 w-[18px] h-[18px] rounded-full shadow transition-transform" style="background-color:#fff" :class="punya(c.id) ? 'translate-x-[18px]' : ''"></span>
                                                </button>
                                            </li>
                                        </template>
                                    </ul>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </section>

        {{-- ═════════════ TAB 2: STRUKTUR MENU ═════════════ --}}
        <section x-show="tab === 'struktur'" x-cloak class="space-y-4">
            <div class="flex items-start gap-2.5 p-3.5 rounded-2xl bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-500/30 text-xs text-violet-800 dark:text-violet-200">
                <x-icon name="info" class="w-4 h-4 shrink-0 mt-px" />
                <p>Susun urutan dengan tombol panah, nonaktifkan menu untuk menyembunyikannya dari semua role, atau tambah sub-menu di bawah menu induk. Perubahan di tab ini langsung tersimpan.</p>
            </div>

            <template x-for="kel in kelompokTerurut()" :key="kel">
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden" x-show="induk(kel).some(m => cocok(m))">
                    <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40">
                        <p class="text-sm font-extrabold text-slate-800 dark:text-slate-100"><span x-text="KELOMPOK[kel].label"></span> <span class="ml-1 text-xs font-semibold text-slate-400" x-text="'· ' + jumlahKelompok(kel) + ' menu'"></span></p>
                        <button type="button" @click="bukaForm(null, { role_default: kel })" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold text-indigo-600 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10"><x-icon name="plus" class="w-3.5 h-3.5" /> Menu</button>
                    </div>

                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        <template x-for="(m, i) in induk(kel).filter(x => cocok(x))" :key="m.id">
                            <li>
                                {{-- Baris menu (dipakai untuk induk maupun sub-menu) --}}
                                <template x-for="baris in [{ m, i, anakKe: false, saudara: induk(kel).length }].concat(anak(m.id).map((c, k) => ({ m: c, i: k, anakKe: true, saudara: anak(m.id).length })))" :key="baris.m.id">
                                    <div class="flex items-center gap-2 sm:gap-3 pr-3 sm:pr-4 py-2.5 hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors"
                                         :class="baris.anakKe ? 'pl-8 sm:pl-12' : 'pl-3 sm:pl-4'">
                                        <div class="flex flex-col shrink-0">
                                            <button type="button" @click="geser(baris.m, -1)" :disabled="baris.i === 0" class="w-6 h-4 flex items-center justify-center text-slate-400 hover:text-indigo-600 disabled:opacity-25 disabled:pointer-events-none" title="Naikkan"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg></button>
                                            <button type="button" @click="geser(baris.m, 1)" :disabled="baris.i === baris.saudara - 1" class="w-6 h-4 flex items-center justify-center text-slate-400 hover:text-indigo-600 disabled:opacity-25 disabled:pointer-events-none" title="Turunkan"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg></button>
                                        </div>
                                        <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                                              :class="baris.anakKe ? 'bg-transparent text-slate-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'" x-html="ikon(baris.m.icon)"></span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate" :class="!baris.m.is_active && 'opacity-50 line-through decoration-slate-300'" x-text="baris.m.name"></p>
                                            <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono truncate max-w-[14rem]" x-text="baris.m.route || 'Induk (tanpa route)'"></span>
                                                <template x-for="r in aksesRole(baris.m.id)" :key="r">
                                                    <span class="px-1.5 py-px rounded text-[10px] font-bold"
                                                          :class="{ 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300': r === 'koordinator', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': r === 'dosen', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': r === 'mahasiswa' }"
                                                          x-text="ROLE[r].label"></span>
                                                </template>
                                            </div>
                                        </div>
                                        <button type="button" role="switch" :aria-checked="baris.m.is_active" @click="alihAktif(baris.m)" :title="baris.m.is_active ? 'Aktif — klik untuk menonaktifkan' : 'Nonaktif — klik untuk mengaktifkan'"
                                                class="relative w-10 h-[22px] rounded-full transition-colors shrink-0" :class="baris.m.is_active ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'">
                                            <span class="absolute top-0.5 left-0.5 w-[18px] h-[18px] rounded-full shadow transition-transform" style="background-color:#fff" :class="baris.m.is_active ? 'translate-x-[18px]' : ''"></span>
                                        </button>
                                        <div class="flex items-center shrink-0">
                                            <button type="button" x-show="!baris.anakKe" @click="bukaForm(null, baris.m)" class="hidden sm:flex w-8 h-8 rounded-lg items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10" title="Tambah sub-menu"><x-icon name="plus" class="w-4 h-4" /></button>
                                            <button type="button" @click="bukaForm(baris.m)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10" title="Ubah"><x-icon name="pencil" class="w-4 h-4" /></button>
                                            <button type="button" @click="konfirmasiHapus(baris.m)" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10" title="Hapus"><x-icon name="trash" class="w-4 h-4" /></button>
                                        </div>
                                    </div>
                                </template>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </section>

        {{-- ═════════════ TAB 3: AKSES KHUSUS USER ═════════════ --}}
        <section x-show="tab === 'user'" x-cloak class="space-y-4">
            <div class="bg-white dark:bg-slate-900 p-3 sm:p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1">
                    <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                    <input type="search" x-model="userQ" @input.debounce.350ms="muatUser()" placeholder="Cari nama atau email user…"
                           class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <select x-model="userRole" @change="muatUser()" class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-700 dark:text-slate-200">
                    <option value="">Semua role</option>
                    <option value="koordinator">Koordinator</option>
                    <option value="dosen">Dosen</option>
                    <option value="mahasiswa">Mahasiswa</option>
                    <option value="super_admin">Super Admin</option>
                </select>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 cursor-pointer select-none">
                    <input type="checkbox" x-model="userKhusus" @change="muatUser()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Hanya yang punya akses khusus
                </label>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                    <span x-text="userMemuat ? 'Memuat…' : userTotal + ' user ditemukan'"></span>
                    <span class="hidden sm:inline">Akses khusus = menu tambahan di luar hak akses role-nya.</span>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800 transition-opacity" :class="userMemuat && 'opacity-50'">
                    <template x-for="u in users" :key="u.hid">
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold flex items-center justify-center text-xs shrink-0" x-text="u.name.charAt(0).toUpperCase()"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate" x-text="u.name"></p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate" x-text="u.email"></p>
                            </div>
                            <div class="hidden sm:flex flex-wrap gap-1 justify-end">
                                <template x-for="r in u.roles" :key="r">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold capitalize"
                                          :class="{ 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300': r === 'super_admin', 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300': r === 'koordinator', 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300': r === 'dosen', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300': r === 'mahasiswa' }"
                                          x-text="r.replace('_', ' ')"></span>
                                </template>
                            </div>
                            <span x-show="u.custom.length" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300 shrink-0" x-text="'+' + u.custom.length + ' khusus'"></span>
                            <button type="button" @click="bukaUser(u)" :disabled="u.roles.includes('super_admin')" class="ui-btn-secondary shrink-0"
                                    :title="u.roles.includes('super_admin') ? 'Super Admin sudah memiliki semua akses' : 'Atur akses khusus'">
                                <x-icon name="key" class="w-4 h-4" /> <span class="hidden sm:inline">Atur</span>
                            </button>
                        </li>
                    </template>
                    <li x-show="!userMemuat && !users.length" class="px-4 py-10 text-center text-sm text-slate-400">Tidak ada user yang cocok.</li>
                </ul>
                <div x-show="userTerakhir > 1" class="px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button type="button" class="ui-btn-secondary" :disabled="userHalaman <= 1" @click="muatUser(userHalaman - 1)">Sebelumnya</button>
                    <span class="text-xs text-slate-500" x-text="'Halaman ' + userHalaman + ' dari ' + userTerakhir"></span>
                    <button type="button" class="ui-btn-secondary" :disabled="userHalaman >= userTerakhir" @click="muatUser(userHalaman + 1)">Berikutnya</button>
                </div>
            </div>
        </section>

        {{-- Bilah simpan (muncul bila ada perubahan hak akses role) --}}
        <div x-show="jumlahPerubahan()" x-cloak class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-6 z-40 sm:w-[30rem]">
            <div class="flex items-center gap-3 p-3 pl-4 rounded-2xl bg-slate-900 text-white shadow-2xl shadow-slate-900/30 ring-1 ring-white/10">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse shrink-0"></span>
                <p class="text-xs font-semibold flex-1"><span x-text="jumlahPerubahan()"></span> perubahan hak akses belum disimpan</p>
                <button type="button" @click="batalkan()" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-300 hover:text-white hover:bg-white/10">Batalkan</button>
                <button type="button" @click="simpanRole()" :disabled="menyimpan" class="px-3.5 py-1.5 rounded-lg text-xs font-bold bg-indigo-500 hover:bg-indigo-400 disabled:opacity-60" x-text="menyimpan ? 'Menyimpan…' : 'Simpan'"></button>
            </div>
        </div>

        {{-- ═════════════ MODAL: TAMBAH / UBAH MENU ═════════════ --}}
        <div x-show="formTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto" @keydown.escape.window="formTerbuka = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-lg w-full my-8 border border-slate-100 dark:border-slate-700 overflow-hidden" @click.outside="formTerbuka = false">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="font-extrabold text-slate-900 dark:text-slate-100" x-text="form.hid ? 'Ubah Menu' : 'Tambah Menu'"></h3>
                    <button type="button" @click="formTerbuka = false" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><x-icon name="x" class="w-4 h-4" /></button>
                </div>
                <form @submit.prevent="simpanMenu()" class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Nama menu</label>
                        <input x-ref="namaMenu" type="text" x-model="form.name" required maxlength="255" placeholder="Contoh: Jadwal Ujian"
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <p x-show="galat.name" class="mt-1 text-xs text-rose-600" x-text="galat.name?.[0]"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Kelompok (bagian sidebar)</label>
                            <select x-model="form.role_default" @change="form.parent_id = ''" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100">
                                <template x-for="(info, k) in KELOMPOK" :key="k"><option :value="k" x-text="info.label" :selected="form.role_default === k"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Induk menu</label>
                            <select x-model="form.parent_id" class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm text-slate-800 dark:text-slate-100">
                                <option value="">— Menu utama —</option>
                                <template x-for="p in indukPilihan()" :key="p.id"><option :value="p.id" x-text="p.name" :selected="String(form.parent_id) === String(p.id)"></option></template>
                            </select>
                            <p x-show="galat.parent_id" class="mt-1 text-xs text-rose-600" x-text="galat.parent_id?.[0]"></p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Route halaman <span class="font-normal text-slate-400">(kosongkan untuk menu induk)</span></label>
                        <input type="text" x-model="form.route" list="daftar-route" placeholder="Ketik untuk mencari, mis. master.skripsi.index" autocomplete="off"
                               class="w-full px-3.5 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-mono text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <datalist id="daftar-route">
                            @foreach($daftarRoute as $r)<option value="{{ $r }}"></option>@endforeach
                        </datalist>
                        <p x-show="galat.route" class="mt-1 text-xs text-rose-600" x-text="galat.route?.[0]"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Ikon</label>
                        <div class="grid grid-cols-8 sm:grid-cols-10 gap-1.5 max-h-32 overflow-y-auto p-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800">
                            @foreach($daftarIkon as $ik)
                                <button type="button" @click="form.icon = '{{ $ik }}'" title="{{ $ik }}"
                                        class="aspect-square rounded-lg flex items-center justify-center transition-all"
                                        :class="form.icon === '{{ $ik }}' ? 'bg-indigo-600 text-white shadow' : 'text-slate-500 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-700'">
                                    <x-icon :name="$ik" class="w-4 h-4" />
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <label class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 cursor-pointer">
                        <span>
                            <span class="block text-sm font-bold text-slate-800 dark:text-slate-100">Menu aktif</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Menu nonaktif disembunyikan dari sidebar semua role.</span>
                        </span>
                        <button type="button" role="switch" :aria-checked="form.is_active" @click="form.is_active = !form.is_active"
                                class="relative w-11 h-6 rounded-full transition-colors shrink-0" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-200 dark:bg-slate-700'">
                            <span class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full shadow transition-transform" style="background-color:#fff" :class="form.is_active ? 'translate-x-5' : ''"></span>
                        </button>
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="formTerbuka = false" class="ui-btn-secondary">Batal</button>
                        <button type="submit" class="ui-btn-primary" :disabled="menyimpan" x-text="menyimpan ? 'Menyimpan…' : (form.hid ? 'Simpan Perubahan' : 'Tambah Menu')"></button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ═════════════ MODAL: HAPUS MENU ═════════════ --}}
        <div x-show="hapusTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4" @keydown.escape.window="hapusTerbuka = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-slate-100 dark:border-slate-700" @click.outside="hapusTerbuka = false">
                <span class="mx-auto w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-500/10 text-rose-600 flex items-center justify-center"><x-icon name="trash" class="w-6 h-6" /></span>
                <h3 class="mt-4 font-extrabold text-slate-900 dark:text-slate-100">Hapus menu ini?</h3>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">Menu <strong class="text-slate-800 dark:text-slate-200" x-text="akanDihapus?.name"></strong> akan dihapus beserta hak aksesnya di semua role & user.</p>
                <div class="mt-5 flex justify-center gap-2">
                    <button type="button" @click="hapusTerbuka = false" class="ui-btn-secondary">Batal</button>
                    <button type="button" @click="hapus()" :disabled="menyimpan" class="ui-btn-danger">Ya, hapus</button>
                </div>
            </div>
        </div>

        {{-- ═════════════ MODAL: AKSES KHUSUS USER ═════════════ --}}
        <div x-show="userTerbuka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto" @keydown.escape.window="userTerbuka = false">
            <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl max-w-2xl w-full my-8 border border-slate-100 dark:border-slate-700 overflow-hidden flex flex-col max-h-[calc(100vh-4rem)]" @click.outside="userTerbuka = false">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-bold flex items-center justify-center shrink-0" x-text="userTerpilih?.name?.charAt(0).toUpperCase()"></span>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-extrabold text-slate-900 dark:text-slate-100 truncate" x-text="'Akses khusus: ' + (userTerpilih?.name ?? '')"></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" x-text="(userTerpilih?.email ?? '') + ' · ' + (userTerpilih?.roles ?? []).join(', ')"></p>
                    </div>
                    <button type="button" @click="userTerbuka = false" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><x-icon name="x" class="w-4 h-4" /></button>
                </div>
                <div class="px-6 py-3 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center gap-2 justify-between">
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Menu bertanda <strong>Dari role</strong> sudah otomatis dimiliki. Centang menu lain untuk akses tambahan.</p>
                    <div class="relative sm:w-56 shrink-0">
                        <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                        <input type="search" x-model="cari" placeholder="Cari menu…" class="w-full pl-9 pr-3 py-1.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs text-slate-700 dark:text-slate-200">
                    </div>
                </div>
                <div class="overflow-y-auto px-6 py-4 space-y-4">
                    <template x-for="kel in ['koordinator', 'dosen', 'mahasiswa', 'super_admin', 'all'].filter(k => induk(k).some(m => cocok(m)))" :key="kel">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5" x-text="KELOMPOK[kel].label"></p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <template x-for="m in induk(kel).filter(x => cocok(x)).flatMap(x => [x, ...anak(x.id)]).filter(x => x.route)" :key="m.id">
                                    <label class="flex items-center gap-2.5 px-3 py-2 rounded-xl border transition-all"
                                           :class="dariRoleUser(m.id) ? 'bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 cursor-default'
                                                  : (userMenu.includes(m.id) ? 'bg-teal-50 dark:bg-teal-500/10 border-teal-300 dark:border-teal-500/40 cursor-pointer' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 cursor-pointer')">
                                        <input type="checkbox" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500 disabled:opacity-50"
                                               :checked="dariRoleUser(m.id) || userMenu.includes(m.id)" :disabled="dariRoleUser(m.id)" @change="alihUser(m.id)">
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-xs font-bold text-slate-800 dark:text-slate-100 truncate" x-text="m.name"></span>
                                            <span class="block text-[10px] font-mono text-slate-400 truncate" x-text="m.route"></span>
                                        </span>
                                        <span x-show="dariRoleUser(m.id)" class="px-1.5 py-px rounded text-[9px] font-bold bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300 shrink-0">Dari role</span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400" x-text="userMenu.length + ' menu tambahan'"></span>
                    <div class="flex gap-2">
                        <button type="button" @click="userTerbuka = false" class="ui-btn-secondary">Batal</button>
                        <button type="button" @click="simpanUser()" :disabled="menyimpan" class="ui-btn-primary" x-text="menyimpan ? 'Menyimpan…' : 'Simpan Akses'"></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
