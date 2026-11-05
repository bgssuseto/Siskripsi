<x-app-layout title="Data Kesediaan Dosen">
    <x-slot:header>Kesediaan Dosen Menguji</x-slot:header>

    @php
        $statusForm = match (true) {
            !$activePeriode => ['Tidak ada periode aktif', 'bg-slate-400', 'text-slate-600 dark:text-slate-300'],
            !$activePeriode->show_form_kesediaan => ['Ditutup', 'bg-slate-400', 'text-slate-700 dark:text-slate-200'],
            (bool) $activePeriode->lock_form_kesediaan => ['Dikunci', 'bg-amber-500', 'text-amber-700 dark:text-amber-300'],
            default => ['Dibuka', 'bg-emerald-500', 'text-emerald-700 dark:text-emerald-300'],
        };
        $persenMengisi = $ringkasan['dosen_berhak'] ? min(100, round($ringkasan['sudah_mengisi'] / $ringkasan['dosen_berhak'] * 100)) : 0;

        $filterPeriode = request('periode_id') ? $periodes->firstWhere('id', (int) request('periode_id')) : null;
        $filterDosen   = request('dosen_id') ? $dosens->firstWhere('id', (int) request('dosen_id')) : null;
        $filterWave    = request('wave_id') ? $waves->firstWhere('id', (int) request('wave_id')) : null;
        $lingkupReset  = collect([
            $filterPeriode ? 'periode ' . $filterPeriode->nama_periode : null,
            $filterWave ? 'gelombang ' . $filterWave->gelombang . ' (' . ucfirst($filterWave->jenis) . ')' : null,
            $filterDosen ? 'dosen ' . $filterDosen->nama_dosen : null,
        ])->filter()->implode(' & ');

        $tabAwal = $errors->has('kesediaan_tanggal_mulai') || $errors->has('kesediaan_tanggal_selesai') ? 'pengaturan' : null;
        $input = 'w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
        $label = 'mb-1.5 block text-xs font-semibold text-slate-600 dark:text-slate-300';
    @endphp

    <div class="space-y-5" x-data="{ importModal: false }">

        {{-- ══════════ Header ══════════ --}}
        <x-page-header title="Kesediaan Dosen Menguji" icon="calendar" accent="emerald"
                       subtitle="Kumpulkan tanggal kesediaan dosen sebagai dasar penjadwalan Sempro & Sidang Skripsi.">
            <x-slot:actions>
                @if($activePeriode)
                    <button type="button" class="ui-btn-secondary" x-data="{ ok: false }"
                            @click="navigator.clipboard.writeText(@js($activePeriode->kesediaan_public_url)); ok = true; setTimeout(() => ok = false, 1600)">
                        <x-icon name="link" class="h-4 w-4" />
                        <span x-text="ok ? 'Link tersalin' : 'Salin link form'">Salin link form</span>
                    </button>
                @endif
                <button type="button" @click="importModal = true" class="ui-btn-primary"><x-icon name="upload" class="h-4 w-4" /> Import Excel</button>
            </x-slot:actions>
        </x-page-header>

        @foreach(['success' => 'emerald', 'error' => 'rose'] as $kunci => $warna)
            @if(session($kunci))
                <div x-data="{ show: true }" x-show="show"
                     class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-medium
                            {{ $warna === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300' }}">
                    <x-icon :name="$warna === 'emerald' ? 'check-circle' : 'warning'" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span class="flex-1">{{ session($kunci) }}</span>
                    <button type="button" @click="show = false" class="opacity-60 hover:opacity-100" aria-label="Tutup"><x-icon name="x" class="h-4 w-4" /></button>
                </div>
            @endif
        @endforeach

        {{-- ══════════ Ringkasan periode aktif ══════════ --}}
        @if($activePeriode)
            <section class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Form dosen</p>
                    <p class="mt-1.5 flex items-center gap-2 text-lg font-bold {{ $statusForm[2] }}">
                        <span class="h-2.5 w-2.5 rounded-full {{ $statusForm[1] }}"></span>{{ $statusForm[0] }}
                    </p>
                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400" title="{{ $activePeriode->nama_periode }}">{{ $activePeriode->nama_periode }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rentang tanggal</p>
                    <p class="mt-1.5 text-sm font-bold leading-snug text-slate-900 dark:text-slate-100">{{ $activePeriode->labelRentangKesediaan() ?? 'Tanpa batas' }}</p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">yang boleh dipilih dosen</p>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sudah mengisi</p>
                    <p class="mt-1.5 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $ringkasan['sudah_mengisi'] }} <span class="text-sm font-medium text-slate-500 dark:text-slate-400">/ {{ $ringkasan['dosen_berhak'] }} dosen</span></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $persenMengisi }}%"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Hari tersedia</p>
                    <p class="mt-1.5 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $ringkasan['jumlah_hari'] }} <span class="text-sm font-medium text-slate-500 dark:text-slate-400">hari</span></p>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">ada dosen yang bersedia</p>
                </div>
            </section>
        @else
            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">
                <x-icon name="warning" class="mt-0.5 h-4 w-4 shrink-0" />
                <span>Tidak ada periode akademik aktif. Aktifkan periode di menu Master Periode untuk mengatur form kesediaan.</span>
            </div>
        @endif

        {{-- ══════════ Tab ══════════ --}}
        <div x-data="{
                tab: @js($tabAwal) || (['data','pengaturan','akses'].includes(location.hash.slice(1)) ? location.hash.slice(1) : null) || (() => { try { return sessionStorage.getItem('kesediaan-tab') } catch (e) { return null } })() || 'data',
                pilih(t) { this.tab = t; try { sessionStorage.setItem('kesediaan-tab', t) } catch (e) {} history.replaceState(null, '', '#' + t); },
             }">
            <div class="mb-4 flex gap-1 rounded-xl border border-slate-200/80 bg-slate-100/70 p-1 dark:border-slate-700 dark:bg-slate-800/60" role="tablist">
                @foreach([
                    ['data', 'Data Kesediaan', 'Data', 'list', $kesediaans->total()],
                    ['pengaturan', 'Pengaturan Form', 'Pengaturan', 'cog', null],
                    ['akses', 'Akses Dosen', 'Akses', 'users', $ringkasan['dosen_berhak'] . '/' . $dosens->count()],
                ] as [$kunci, $judul, $judulPendek, $ikon, $hitung])
                    <button type="button" role="tab" @click="pilih('{{ $kunci }}')" :aria-selected="tab === '{{ $kunci }}'"
                            class="flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg px-2 py-2 text-[13px] font-semibold transition sm:flex-none sm:gap-2 sm:px-3.5 sm:text-sm"
                            :class="tab === '{{ $kunci }}' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-900 dark:text-white' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'">
                        <x-icon :name="$ikon" class="hidden h-4 w-4 sm:block" /><span class="sm:hidden">{{ $judulPendek }}</span><span class="hidden sm:inline">{{ $judul }}</span>
                        @if($hitung !== null)
                            <span class="rounded-md px-1.5 py-px text-[11px] font-bold"
                                  :class="tab === '{{ $kunci }}' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' : 'bg-slate-200/70 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $hitung }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- ───────── Tab: Data kesediaan ───────── --}}
            <section x-show="tab === 'data'" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                <form method="GET" action="{{ route('master.kesediaan-dosen.index') }}"
                      class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center dark:border-slate-700">
                    <div class="relative min-w-0 flex-1 sm:max-w-sm">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama dosen, NIDN, atau catatan…"
                               class="{{ $input }} !py-2 pl-9">
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-filter-popover :active="request()->hasAny(['dosen_id','periode_id','wave_id','per_page'])">
                            <div>
                                <label class="{{ $label }}">Periode</label>
                                <select name="periode_id" class="{{ $input }} cursor-pointer">
                                    <option value="">Semua periode</option>
                                    @foreach ($periodes as $p)
                                        <option value="{{ $p->id }}" @selected(request('periode_id', $activePeriode->id ?? null) == $p->id)>{{ $p->nama_periode }}{{ $p->aktif ? ' (Aktif)' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Gelombang</label>
                                <select name="wave_id" class="{{ $input }} cursor-pointer">
                                    <option value="">Semua gelombang</option>
                                    @foreach ($waves->when(request('periode_id'), fn ($c) => $c->where('periode_id', (int) request('periode_id'))) as $w)
                                        <option value="{{ $w->id }}" @selected((string) request('wave_id') === (string) $w->id)>
                                            {{ request('periode_id') ? '' : ($w->periode->nama_periode ?? '') . ' — ' }}Gel. {{ $w->gelombang }} ({{ ucfirst($w->jenis) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Dosen</label>
                                <select name="dosen_id" class="{{ $input }} cursor-pointer">
                                    <option value="">Semua dosen</option>
                                    @foreach ($dosens as $d)
                                        <option value="{{ $d->id }}" @selected(request('dosen_id') == $d->id)>{{ $d->nama_dosen }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Tampilkan</label>
                                <select name="per_page" class="{{ $input }} cursor-pointer">
                                    @foreach([10, 25, 100] as $n)
                                        <option value="{{ $n }}" @selected(request('per_page', 25) == $n)>{{ $n }} per halaman</option>
                                    @endforeach
                                </select>
                            </div>
                        </x-filter-popover>
                        @if(request()->hasAny(['search','dosen_id','periode_id','wave_id','per_page']))
                            <a href="{{ route('master.kesediaan-dosen.index') }}" class="ui-btn-secondary"><x-icon name="x" class="h-4 w-4" /> Bersihkan</a>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 sm:ml-auto dark:text-slate-400">
                        {{ $kesediaans->total() }} entri{{ $lingkupReset ? ' · ' . $lingkupReset : ' · semua periode' }}
                    </p>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:border-slate-700 dark:bg-slate-900/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Dosen</th>
                                <th class="hidden px-4 py-3 sm:table-cell">Tanggal bersedia</th>
                                <th class="hidden px-4 py-3 md:table-cell">Gelombang</th>
                                <th class="hidden px-4 py-3 lg:table-cell">Catatan</th>
                                <th class="w-12 px-4 py-3"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700/70">
                            @forelse ($kesediaans as $item)
                                @php
                                    $tanggal = collect($item['dates'])->map(fn ($t) => \Carbon\Carbon::parse($t)->locale('id'))->sort()->values();
                                    $gelombang = $item['wave'] ? 'Gel. ' . $item['wave']->gelombang . ' · ' . ucfirst($item['wave']->jenis) : ($item['periode']->nama_periode ?? null);
                                    $catatan = implode(', ', $item['keterangans']);
                                @endphp
                                <tr class="align-top transition-colors hover:bg-slate-50/70 dark:hover:bg-slate-700/30" x-data="{ semua: false }">
                                    <td class="px-4 py-3.5">
                                        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ $item['dosen']->nama_dosen ?? '-' }}</p>
                                        @if($item['dosen']?->nidn)
                                            <p class="mt-0.5 font-mono text-[11px] text-slate-500 dark:text-slate-400">NIDN {{ $item['dosen']->nidn }}</p>
                                        @endif
                                        {{-- Info kolom yang disembunyikan di layar kecil --}}
                                        <div class="mt-1.5 flex flex-wrap gap-x-2 gap-y-1 text-[11px] text-slate-500 md:hidden dark:text-slate-400">
                                            @if($gelombang)<span class="font-semibold text-indigo-600 dark:text-indigo-300">{{ $gelombang }}</span>@endif
                                            @if($catatan)<span>· {{ $catatan }}</span>@endif
                                        </div>
                                        <div class="mt-2.5 sm:hidden">@include('master.kesediaan_dosen._tanggal')</div>
                                    </td>
                                    <td class="hidden px-4 py-3.5 sm:table-cell">
                                        @include('master.kesediaan_dosen._tanggal')
                                    </td>
                                    <td class="hidden px-4 py-3.5 md:table-cell">
                                        @if($gelombang)
                                            <span class="inline-flex whitespace-nowrap rounded-md border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $gelombang }}</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="hidden px-4 py-3.5 text-xs text-slate-600 lg:table-cell dark:text-slate-300">{{ $catatan ?: '—' }}</td>
                                    <td class="px-4 py-3.5 text-right">
                                        <form method="POST" action="{{ route('master.kesediaan-dosen.destroy-group') }}"
                                              onsubmit="event.preventDefault(); window.confirmDialog('Seluruh tanggal kesediaan dosen ini untuk gelombang tersebut akan dihapus.', { title: 'Hapus kesediaan?', confirmText: 'Ya, Hapus' }).then(ok => ok && this.submit()); return false;">
                                            @csrf
                                            <input type="hidden" name="dosen_id" value="{{ $item['dosen_id'] }}">
                                            <input type="hidden" name="wave_id" value="{{ $item['wave_id'] }}">
                                            <button type="submit" title="Hapus kesediaan ini"
                                                    class="rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-400">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-14 text-center">
                                        <span class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500"><x-icon name="calendar" class="h-5 w-5" /></span>
                                        <p class="font-semibold text-slate-700 dark:text-slate-200">Belum ada data kesediaan</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Dosen mengisi lewat dashboard masing-masing atau link form publik.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/60 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-900/30">
                    <div class="min-w-0">{{ $kesediaans->links() }}</div>
                    @if($kesediaans->total() > 0)
                        @php
                            $resetTitle = $lingkupReset ? "Reset Kesediaan untuk {$lingkupReset}?" : 'Hapus SEMUA Data Kesediaan?';
                            $resetMessage = $lingkupReset
                                ? 'Dosen dapat mengisi ulang kesediaannya setelah direset. Tindakan ini tidak dapat dibatalkan.'
                                : 'Seluruh data kesediaan dosen (semua dosen, semua periode/gelombang) akan dihapus. Tindakan ini tidak dapat dibatalkan.';
                        @endphp
                        <form action="{{ route('master.kesediaan-dosen.destroy-all') }}" method="POST" class="shrink-0"
                              onsubmit="event.preventDefault(); window.confirmDialog(@js($resetMessage), { title: @js($resetTitle), confirmText: 'Ya, Lanjutkan' }).then(ok => ok && this.submit()); return false;">
                            @csrf
                            @method('DELETE')
                            @if($filterPeriode)<input type="hidden" name="periode_id" value="{{ $filterPeriode->id }}">@endif
                            @if($filterWave)<input type="hidden" name="wave_id" value="{{ $filterWave->id }}">@endif
                            @if($filterDosen)<input type="hidden" name="dosen_id" value="{{ $filterDosen->id }}">@endif
                            <button type="submit" class="ui-btn-danger-soft">
                                <x-icon name="refresh" class="h-4 w-4" /> {{ $lingkupReset ? 'Reset Kesediaan (sesuai filter)' : 'Hapus Semua Kesediaan' }}
                            </button>
                        </form>
                    @endif
                </div>
            </section>

            {{-- ───────── Tab: Pengaturan form ───────── --}}
            <section x-show="tab === 'pengaturan'" x-cloak class="grid grid-cols-1 gap-5 lg:grid-cols-5">
                @if($activePeriode)
                    <form method="POST" action="{{ route('master.kesediaan-dosen.settings') }}"
                          class="rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-3 dark:border-slate-700 dark:bg-slate-800/80">
                        @csrf
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Pengaturan form kesediaan</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Berlaku untuk periode <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $activePeriode->nama_periode }}</span> — form di dashboard dosen &amp; link publik.</p>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-700">
                            {{-- Buka / tutup --}}
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Tampilkan form ke dosen</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Bila ditutup, form tidak muncul di dashboard dosen dan link publik nonaktif.</p>
                                </div>
                                <div class="inline-flex shrink-0 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-600 dark:bg-slate-900/60">
                                    @foreach([1 => 'Buka', 0 => 'Tutup'] as $nilai => $teks)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="show_form_kesediaan" value="{{ $nilai }}" class="peer sr-only" @checked((int) $activePeriode->show_form_kesediaan === $nilai)>
                                            <span class="block rounded-lg px-4 py-1.5 text-sm font-semibold text-slate-500 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 dark:text-slate-400 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">{{ $teks }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Kunci --}}
                            <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Izinkan dosen mengubah isian</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Kunci saat penjadwalan dimulai agar data tidak berubah lagi.</p>
                                </div>
                                <div class="inline-flex shrink-0 rounded-xl border border-slate-200 bg-slate-100 p-1 dark:border-slate-600 dark:bg-slate-900/60">
                                    @foreach([0 => 'Terbuka', 1 => 'Dikunci'] as $nilai => $teks)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="lock_form_kesediaan" value="{{ $nilai }}" class="peer sr-only" @checked((int) $activePeriode->lock_form_kesediaan === $nilai)>
                                            <span class="block rounded-lg px-4 py-1.5 text-sm font-semibold text-slate-500 transition peer-checked:bg-white peer-checked:text-slate-900 peer-checked:shadow-sm peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 dark:text-slate-400 dark:peer-checked:bg-slate-700 dark:peer-checked:text-white">{{ $teks }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Rentang tanggal --}}
                            <div class="px-5 py-4">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">Rentang Tanggal Kesediaan</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Dosen hanya bisa memilih tanggal di dalam rentang ini. Kosongkan untuk tanpa batas.
                                    @if($activePeriode->labelRentangKesediaan())
                                        <span class="font-semibold text-indigo-600 dark:text-indigo-300">Berlaku: {{ $activePeriode->labelRentangKesediaan() }}</span>
                                    @endif
                                </p>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label for="kesediaan_tanggal_mulai" class="{{ $label }}">Dari tanggal</label>
                                        <input type="date" id="kesediaan_tanggal_mulai" name="kesediaan_tanggal_mulai" class="{{ $input }}"
                                               value="{{ old('kesediaan_tanggal_mulai', $activePeriode->kesediaan_tanggal_mulai?->format('Y-m-d')) }}">
                                    </div>
                                    <div>
                                        <label for="kesediaan_tanggal_selesai" class="{{ $label }}">Sampai tanggal</label>
                                        <input type="date" id="kesediaan_tanggal_selesai" name="kesediaan_tanggal_selesai" class="{{ $input }}"
                                               value="{{ old('kesediaan_tanggal_selesai', $activePeriode->kesediaan_tanggal_selesai?->format('Y-m-d')) }}">
                                    </div>
                                </div>
                                @error('kesediaan_tanggal_selesai')
                                    <p class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-700 dark:bg-slate-900/30">
                            <button type="submit" class="ui-btn-primary"><x-icon name="save" class="h-4 w-4" /> Simpan pengaturan</button>
                        </div>
                    </form>

                    {{-- Link publik --}}
                    <div class="flex flex-col rounded-2xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2 dark:border-slate-700 dark:bg-slate-800/80" x-data="{ ok: false }">
                        <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Link form publik</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Bagikan ke dosen (mis. lewat WhatsApp) — bisa diisi tanpa login.</p>
                            </div>
                            @if($activePeriode->isKesediaanPublicLinkActive())
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>Aktif</span>
                            @else
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold text-slate-600 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span>Nonaktif</span>
                            @endif
                        </div>
                        <div class="flex-1 space-y-3 px-5 py-4">
                            <div class="flex items-stretch overflow-hidden rounded-xl border border-slate-200 dark:border-slate-600">
                                <input id="kesediaan-public-link" type="text" readonly value="{{ $activePeriode->kesediaan_public_url }}"
                                       class="min-w-0 flex-1 border-0 bg-slate-50 px-3 py-2.5 font-mono text-xs text-slate-700 focus:ring-0 dark:bg-slate-900/60 dark:text-slate-200"
                                       @focus="$el.select()">
                                <button type="button" title="Salin link"
                                        @click="navigator.clipboard.writeText(document.getElementById('kesediaan-public-link').value); ok = true; setTimeout(() => ok = false, 1600)"
                                        class="flex shrink-0 items-center gap-1.5 border-l border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                                    <x-icon name="clipboard-list" class="h-4 w-4" /><span x-text="ok ? 'Tersalin' : 'Salin'">Salin</span>
                                </button>
                            </div>
                            <p class="text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                Aktif selama form <strong class="font-semibold text-slate-700 dark:text-slate-200">dibuka</strong> dan <strong class="font-semibold text-slate-700 dark:text-slate-200">tidak dikunci</strong> — tidak bergantung pada jadwal gelombang pendaftaran.
                            </p>
                        </div>
                        <div class="border-t border-slate-100 px-5 py-3 dark:border-slate-700">
                            <form method="POST" action="{{ route('master.kesediaan-dosen.reset-public-token') }}"
                                  onsubmit="event.preventDefault(); window.confirmDialog('Link lama yang sudah dibagikan akan langsung tidak berlaku.', { title: 'Buat link baru?', type: 'warning', confirmText: 'Ya, Buat Baru' }).then(ok => ok && this.submit()); return false;">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400">
                                    <x-icon name="refresh" class="h-3.5 w-3.5" /> Buat link baru (link lama tidak berlaku)
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 lg:col-span-5 dark:border-slate-600 dark:text-slate-400">
                        Aktifkan periode akademik terlebih dahulu untuk mengatur form kesediaan.
                    </div>
                @endif
            </section>

            {{-- ───────── Tab: Akses dosen ───────── --}}
            <section x-show="tab === 'akses'" x-cloak x-data="{ cari: '', status: 'semua' }"
                     class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800/80">
                <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center dark:border-slate-700">
                    <div class="relative min-w-0 flex-1 sm:max-w-sm">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input type="search" x-model.debounce.150ms="cari" placeholder="Cari dosen…" class="{{ $input }} !py-2 pl-9" aria-label="Cari dosen">
                    </div>
                    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-100 p-1 text-xs font-semibold dark:border-slate-600 dark:bg-slate-900/60">
                        @foreach(['semua' => 'Semua', 'aktif' => 'Boleh mengisi', 'nonaktif' => 'Dibatasi'] as $nilai => $teks)
                            <button type="button" @click="status = '{{ $nilai }}'"
                                    class="rounded-lg px-3 py-1.5 transition"
                                    :class="status === '{{ $nilai }}' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-500 dark:text-slate-400'">{{ $teks }}</button>
                        @endforeach
                    </div>
                    <p class="text-xs text-slate-500 sm:ml-auto dark:text-slate-400">Dosen yang dibatasi tidak bisa mengisi form kesediaan.</p>
                </div>

                <ul class="grid grid-cols-1 divide-y divide-slate-100 lg:grid-cols-2 lg:divide-y-0 dark:divide-slate-700/70">
                    @foreach($dosens as $d)
                        <li class="flex items-center justify-between gap-4 px-4 py-3 lg:border-b lg:border-slate-100 lg:odd:border-r dark:lg:border-slate-700/70"
                            data-nama="{{ mb_strtolower($d->nama_dosen . ' ' . $d->nidn) }}"
                            x-show="(!cari || $el.dataset.nama.includes(cari.toLowerCase())) && (status === 'semua' || status === '{{ $d->can_fill_kesediaan ? 'aktif' : 'nonaktif' }}')">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100" title="{{ $d->nama_dosen }}">{{ $d->nama_dosen }}</p>
                                <p class="font-mono text-[11px] text-slate-500 dark:text-slate-400">NIDN {{ $d->nidn ?: '—' }}</p>
                            </div>
                            <form method="POST" action="{{ route('master.kesediaan-dosen.toggle-access', $d->hash_id) }}" class="flex shrink-0 items-center gap-2.5">
                                @csrf
                                <span class="hidden text-xs font-semibold sm:inline {{ $d->can_fill_kesediaan ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">{{ $d->can_fill_kesediaan ? 'Boleh mengisi' : 'Dibatasi' }}</span>
                                <label class="relative inline-flex cursor-pointer items-center" title="{{ $d->can_fill_kesediaan ? 'Batasi dosen ini' : 'Izinkan dosen ini' }}">
                                    <input type="checkbox" class="peer sr-only" @checked($d->can_fill_kesediaan) onchange="this.form.submit()" aria-label="Akses pengisian {{ $d->nama_dosen }}">
                                    <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-indigo-500 dark:bg-slate-600"></span>
                                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                </label>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        {{-- ══════════ Modal import ══════════ --}}
        <div x-show="importModal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center" @keydown.escape.window="importModal = false">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="importModal = false"></div>
            <div class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Import kesediaan dari Excel</h3>
                    <button type="button" @click="importModal = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-700" aria-label="Tutup"><x-icon name="x" class="h-4 w-4" /></button>
                </div>
                <form method="POST" action="{{ route('master.kesediaan-dosen.import') }}" enctype="multipart/form-data" class="space-y-4 px-5 py-4">
                    @csrf
                    <ul class="list-disc space-y-1 rounded-xl bg-slate-50 py-3 pl-8 pr-4 text-xs leading-relaxed text-slate-600 dark:bg-slate-900/50 dark:text-slate-300">
                        <li>Export jawaban Google Form dengan kolom <b>Nama</b> dan <b>Hari/Tanggal yang dipilih</b>.</li>
                        <li>Nama dosen dicocokkan otomatis ke Master Dosen.</li>
                        <li>Jam diasumsikan <b>08:00–17:00</b>.</li>
                    </ul>
                    <div>
                        <label class="{{ $label }}">Periode akademik <span class="text-rose-500">*</span></label>
                        <select name="periode_id" required class="{{ $input }} cursor-pointer">
                            @foreach($periodes as $p)
                                <option value="{{ $p->id }}" @selected($activePeriode && $activePeriode->id === $p->id)>{{ $p->nama_periode }}{{ $p->aktif ? ' (Aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">Gelombang (opsional)</label>
                        <select name="wave_id" class="{{ $input }} cursor-pointer">
                            <option value="">Tidak spesifik gelombang</option>
                            @foreach($waves as $w)
                                <option value="{{ $w->id }}">{{ $w->periode->nama_periode ?? '' }} — Gel. {{ $w->gelombang }} ({{ ucfirst($w->jenis) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $label }}">File Excel <span class="text-rose-500">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="{{ $input }} !py-2 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div class="flex justify-end gap-2 border-t border-slate-100 pt-4 dark:border-slate-700">
                        <button type="button" @click="importModal = false" class="ui-btn-secondary">Batal</button>
                        <button type="submit" class="ui-btn-primary"><x-icon name="upload" class="h-4 w-4" /> Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
