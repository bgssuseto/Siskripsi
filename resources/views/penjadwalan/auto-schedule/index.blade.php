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
                    Usulan jadwal dibuat otomatis dari irisan kesediaan dosen yang terlibat & ruang yang masih kosong — tinjau dulu sebelum diterapkan.
                </p>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('jadwal.auto-plot.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="generate" value="1">

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
                    <h2 class="text-sm font-bold text-slate-800 dark:text-slate-100">Usulan Jadwal</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Centang baris yang ingin diterapkan, lalu klik "Terapkan Jadwal Terpilih".</p>
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
                                <th class="py-3.5 px-4">Tanggal &amp; Jam Usulan</th>
                                <th class="py-3.5 px-4">Ruang</th>
                                <th class="py-3.5 px-4">Dosen Terlibat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                            @foreach($proposals as $p)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors align-top">
                                <td class="py-3.5 px-4">
                                    <input type="checkbox" name="selected[]" value="{{ $p['sidang_id'] }}" checked class="proposal-checkbox rounded border-slate-300 dark:border-slate-600 text-violet-600 focus:ring-violet-500">
                                    <input type="hidden" name="proposals[{{ $p['sidang_id'] }}][tanggal]" value="{{ $p['tanggal'] }}">
                                    <input type="hidden" name="proposals[{{ $p['sidang_id'] }}][jam]" value="{{ $p['jam'] }}">
                                    <input type="hidden" name="proposals[{{ $p['sidang_id'] }}][ruang_id]" value="{{ $p['ruang_id'] }}">
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $p['nama'] }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $p['nim'] }} &middot; {{ $p['jenis_label'] }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                    {{ \Carbon\Carbon::parse($p['tanggal'])->translatedFormat('d M Y') }}<br>
                                    <span class="text-xs text-slate-400 dark:text-slate-500">{{ $p['jam'] }} WIB</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">{{ $p['ruang_nama'] }}</td>
                                <td class="py-3.5 px-4 text-xs text-slate-500 dark:text-slate-400">
                                    @foreach($p['dosen_display'] as $line)
                                    <div>{{ $line }}</div>
                                    @endforeach
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
