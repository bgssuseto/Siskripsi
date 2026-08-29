<x-app-layout title="Penjadwalan - Asisten Plotting Otomatis">
    <x-slot:header>Asisten Plotting Jadwal Otomatis</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-300 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </span>
                    Asisten Plotting Jadwal Otomatis
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Usulan jadwal dibuat otomatis dari irisan kesediaan dosen yang terlibat & ruang Master Ruang yang masih kosong, dibatasi jam operasional <strong>09.00–17.00</strong>. Setiap baris bisa <strong>ditinjau dan diedit langsung</strong> — tanggal, jam, ruangan, maupun dewan penguji — sebelum diterapkan.
                    Ketua/Penguji 1 yang belum diisi dipilihkan otomatis sesuai <strong>Rule Komposisi Dosen Penguji</strong> (pasangan terlarang tidak akan dipasangkan) dan <strong>jenjang jabatan fungsional</strong> (Asisten Ahli &lt; Lektor &lt; Lektor Kepala &lt; Guru Besar — Penguji 1 tidak akan melebihi jenjang Ketua Penguji), dengan prioritas dosen yang beban mengujinya masih di bawah jumlah mahasiswa yang ia luluskan. Ruang diusahakan tidak berpindah-pindah bagi dewan penguji yang sama dalam satu hari.
                </p>
            </div>
        </div>

        @if(!empty($sidangIds))
        <div class="flex items-center justify-between gap-3 bg-violet-50 dark:bg-violet-500/10 border border-violet-200 dark:border-violet-800 rounded-2xl p-4 text-sm">
            <span class="font-semibold text-violet-800 dark:text-violet-300">
                🎯 Mode Terpilih — hanya menjadwalkan {{ count($sidangIds) }} mahasiswa yang Anda pilih dari daftar, bukan seluruh gelombang.
            </span>
            <a href="{{ route('jadwal.auto-plot.index', ['generate' => 1, 'periode_id' => $selectedPeriodeId, 'jenis' => $jenis, 'gelombang' => $selectedGelombang, 'slot_menit' => $slotMinutes]) }}"
               class="text-xs font-bold text-violet-700 dark:text-violet-300 underline hover:no-underline whitespace-nowrap">
                Batalkan, tampilkan semua
            </a>
        </div>
        @endif

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('jadwal.auto-plot.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="generate" value="1">
                @foreach($sidangIds as $sid)
                    <input type="hidden" name="ids[]" value="{{ $sid }}">
                @endforeach

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Periode Akademik</label>
                    <select name="periode_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                        @foreach($periodes as $p)
                        <option value="{{ $p->id }}" {{ (string) $selectedPeriodeId === (string) $p->id ? 'selected' : '' }}>
                            {{ $p->nama_periode }} {{ $p->aktif ? '(Aktif)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Jenis</label>
                    <select name="jenis" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                        <option value="skripsi" {{ $jenis === 'skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                        <option value="sempro" {{ $jenis === 'sempro' ? 'selected' : '' }}>Seminar Proposal (Sempro)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Gelombang</label>
                    <select name="gelombang" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                        <option value="">-- Semua Gelombang --</option>
                        @foreach($gelombangOptions as $g)
                        <option value="{{ $g }}" {{ (string) $selectedGelombang === (string) $g ? 'selected' : '' }}>Gelombang {{ $g }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Durasi Slot</label>
                    <select name="slot_menit" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all">
                        @foreach([30, 60, 90, 120] as $m)
                        <option value="{{ $m }}" {{ $slotMinutes === $m ? 'selected' : '' }}>{{ $m }} menit</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Buat Usulan Jadwal
                    </button>
                </div>
            </form>
        </div>

        @if($generated)

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Berhasil Diusulkan</p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1.5">{{ count($proposals) }}</p>
            </div>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tidak Bisa Dijadwalkan</p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1.5">{{ count($unresolved) }}</p>
            </div>
        </div>

        <!-- Usulan Jadwal -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Usulan Jadwal — Tinjau &amp; Edit</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Ubah tanggal/jam/ruang/penguji jika perlu, centang baris yang sudah oke, lalu klik "Terapkan Jadwal Terpilih". Bentrok jadwal & pelanggaran Rule Komposisi Penguji tetap divalidasi ulang saat diterapkan.</p>
                </div>
            </div>

            @if(count($proposals) > 0)
            <form method="POST" action="{{ route('jadwal.auto-plot.apply') }}">
                @csrf
                <input type="hidden" name="periode_id" value="{{ $selectedPeriodeId }}">
                <input type="hidden" name="jenis" value="{{ $jenis }}">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200/80 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-4 w-10">
                                    <input type="checkbox" onclick="document.querySelectorAll('.proposal-checkbox').forEach(c => c.checked = this.checked)" checked
                                           class="rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                </th>
                                <th class="py-3.5 px-4">Mahasiswa</th>
                                <th class="py-3.5 px-4 min-w-[150px]">Tanggal</th>
                                <th class="py-3.5 px-4 min-w-[180px]">Jam</th>
                                <th class="py-3.5 px-4 min-w-[140px]">Ruang</th>
                                <th class="py-3.5 px-4 min-w-[320px]">Dewan Penguji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                            @foreach($proposals as $p)
                            @php
                                $jamParts = array_map('trim', explode('-', $p['jam']));
                                $jamMulai = $jamParts[0] ?? '';
                                $jamSelesai = $jamParts[1] ?? '';
                                $fieldPrefix = "proposals[{$p['sidang_id']}]";
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors align-top">
                                <td class="py-3.5 px-4">
                                    <input type="checkbox" name="selected[]" value="{{ $p['sidang_id'] }}" checked class="proposal-checkbox rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $p['nama'] }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $p['nim'] }} &middot; {{ $p['jenis_label'] }}</div>
                                    @if(!empty($p['auto_penguji']))
                                    <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-300">⚡ Penguji Otomatis</span>
                                    @endif
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ $p['dosen_display'][0] ?? '' }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <input type="date" name="{{ $fieldPrefix }}[tanggal]" value="{{ $p['tanggal'] }}"
                                           class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-1">
                                        <select name="{{ $fieldPrefix }}[jam_mulai]" class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500">
                                            @foreach($jamOptions as $j)
                                            <option value="{{ $j }}" {{ $j === $jamMulai ? 'selected' : '' }}>{{ $j }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-slate-400 text-xs">-</span>
                                        <select name="{{ $fieldPrefix }}[jam_selesai]" class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500">
                                            @foreach($jamOptions as $j)
                                            <option value="{{ $j }}" {{ $j === $jamSelesai ? 'selected' : '' }}>{{ $j }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <select name="{{ $fieldPrefix }}[ruang_id]" class="w-full px-2.5 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500">
                                        @foreach($ruangs as $r)
                                        <option value="{{ $r->id }}" {{ (int) $r->id === (int) $p['ruang_id'] ? 'selected' : '' }}>{{ $r->kode_ruangan }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="py-3.5 px-4">
                                    @if(!$p['is_sempro'])
                                    <div class="space-y-1.5">
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Ketua Penguji</label>
                                            <select name="{{ $fieldPrefix }}[ketua_penguji_id]" class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500">
                                                @foreach($dosens as $d)
                                                <option value="{{ $d->id }}" {{ (int) $d->id === (int) $p['ketua_penguji_id'] ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Penguji 1</label>
                                            <select name="{{ $fieldPrefix }}[anggota_penguji_1_id]" class="w-full px-2 py-1.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-violet-500">
                                                @foreach($dosens as $d)
                                                <option value="{{ $d->id }}" {{ (int) $d->id === (int) $p['anggota_penguji_1_id'] ? 'selected' : '' }}>{{ $d->nama_dosen }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500">Penguji 2 otomatis = Pembimbing Utama.</div>
                                    </div>
                                    @else
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        @foreach($p['dosen_display'] as $line)
                                        <div>{{ $line }}</div>
                                        @endforeach
                                    </div>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-xl shadow-md hover:shadow-lg transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Terapkan Jadwal Terpilih
                    </button>
                </div>
            </form>
            @else
            <div class="px-5 py-10 text-center text-slate-400 dark:text-slate-500 text-sm">
                Tidak ada usulan jadwal yang bisa dibuat untuk filter ini.
            </div>
            @endif
        </div>

        <!-- Tidak Bisa Dijadwalkan -->
        @if(count($unresolved) > 0)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Tidak Bisa Dijadwalkan Otomatis</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Perlu ditindaklanjuti manual — biasanya karena kesediaan dosen belum lengkap atau dewan penguji belum diisi.</p>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach($unresolved as $u)
                <div class="px-5 py-3 flex items-start justify-between gap-3">
                    <div>
                        <div class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $u['nama'] }}</div>
                        <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $u['nim'] }} &middot; {{ $u['jenis_label'] }}</div>
                    </div>
                    <span class="text-xs text-amber-600 dark:text-amber-400 font-medium text-right max-w-md">{{ $u['reason'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @endif

    </div>
</x-app-layout>
