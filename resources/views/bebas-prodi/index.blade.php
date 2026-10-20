<x-app-layout title="Bebas Prodi">
@php
    $badge = [
        'menunggu'  => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800',
        'disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800',
        'ditolak'   => 'bg-rose-100 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
    ];
    $label = ['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
    $isSuperAdmin = Auth::user()->isSuperAdmin();
@endphp
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6"
     x-data="{ modal: false, mode: 'disetujui', action: '', nama: '', kaprodiModal: {{ $errors->hasAny(['nama', 'mulai_menjabat', 'selesai_menjabat']) ? 'true' : 'false' }} }">

    <x-page-header title="Bebas Prodi" subtitle="Verifikasi pengajuan Surat Keterangan Bebas Prodi mahasiswa." icon="badge-check" accent="teal">
            <x-slot:actions>
                <span class="ui-chip"><x-icon name="tag" class="w-3.5 h-3.5" /> Nomor berikutnya: <strong class="font-mono text-slate-900 dark:text-white">TI/BP/{{ $nextNomor }}</strong></span>
            </x-slot:actions>
        </x-page-header>

    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs text-rose-800 dark:text-rose-300">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 grid grid-cols-3 gap-3">
            @foreach(['menunggu' => 'ring-amber-500', 'disetujui' => 'ring-emerald-500', 'ditolak' => 'ring-rose-500'] as $st => $ring)
                <a href="{{ route('bebas-prodi.index', ['status' => $status === $st ? null : $st, 'q' => $search ?: null]) }}"
                   class="stat-card rounded-2xl p-4 shadow-sm transition-all {{ $status === $st ? 'ring-2 ' . $ring : '' }}">
                    <p class="stat-label text-[11px] uppercase tracking-wider">{{ $label[$st] }}</p>
                    <p class="stat-value text-2xl mt-1">{{ $counts[$st] ?? 0 }}</p>
                </a>
            @endforeach
        </div>

        <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-4 border border-slate-200/80 dark:border-slate-700 space-y-1.5">
            <p class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Kaprodi Penandatangan</p>
            @if($kaprodiSekarang)
                <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $kaprodiSekarang->nama }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Masa jabatan: {{ $kaprodiSekarang->masa_jabatan }}</p>
                <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400"><x-icon name="check" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Tanda tangan elektronik (QR Code) dibuat otomatis di setiap surat</p>
            @else
                <p class="text-[11px] font-bold text-rose-600 dark:text-rose-400">Belum ada data Kaprodi.</p>
            @endif
            @if($isSuperAdmin)
                <button type="button" @click="kaprodiModal = true" class="mt-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-[11px] font-bold rounded-lg">Kelola Kaprodi</button>
            @endif
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700 overflow-hidden">
        <form method="GET" class="p-4 border-b border-slate-100 dark:border-slate-700 flex flex-wrap gap-2 items-center">
            @if($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, NIM, atau nomor surat..."
                   class="flex-1 min-w-[200px] px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200">
            <button class="px-4 py-2 bg-slate-800 dark:bg-slate-600 text-white text-xs font-bold rounded-xl">Cari</button>
            @if($status || $search)
                <a href="{{ route('bebas-prodi.index') }}" class="px-3 py-2 text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3 text-left">Mahasiswa</th>
                        <th class="px-4 py-3 text-left">Jalur</th>
                        <th class="px-4 py-3 text-left">Berkas &amp; Link</th>
                        <th class="px-4 py-3 text-left">Diajukan</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($pengajuans as $p)
                        <tr class="align-top">
                            <td class="px-4 py-3">
                                <p class="font-extrabold text-slate-800 dark:text-slate-200">{{ $p->nama_mahasiswa }}</p>
                                <span class="nim-pill inline-block mt-1 px-2 py-0.5 rounded-md bg-sky-50 text-sky-700 font-mono text-[10px] dark:bg-sky-500/15 dark:text-sky-200">{{ $p->nim }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300 font-semibold">{{ $p->jalur === 'jurnal' ? 'Jurnal' : 'Sidang' }}</td>
                            <td class="px-4 py-3 space-y-1">
                                <a href="{{ route('bebas-prodi.berkas', $p) }}" target="_blank" class="block font-bold text-teal-700 dark:text-teal-400 hover:underline"><x-icon name="document" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Berkas PDF</a>
                                @if($p->link_poster)<a href="{{ $p->link_poster }}" target="_blank" rel="noopener noreferrer" class="block text-sky-700 dark:text-sky-400 hover:underline">Poster Skripsi</a>@endif
                                <a href="{{ $p->link_linkedin }}" target="_blank" rel="noopener noreferrer" class="block text-sky-700 dark:text-sky-400 hover:underline">LinkedIn</a>
                                @if($p->link_program)<a href="{{ $p->link_program }}" target="_blank" rel="noopener noreferrer" class="block text-sky-700 dark:text-sky-400 hover:underline">Program Aplikasi</a>@endif
                                @if($p->link_jurnal)<a href="{{ $p->link_jurnal }}" target="_blank" rel="noopener noreferrer" class="block text-sky-700 dark:text-sky-400 hover:underline">Artikel Jurnal</a>@endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ $p->submitted_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block px-2 py-0.5 rounded-lg border text-[10px] font-extrabold {{ $badge[$p->status] ?? '' }}">{{ $label[$p->status] ?? $p->status }}</span>
                                @if($p->status === 'disetujui')
                                    <p class="mt-1 font-mono text-[10px] text-slate-600 dark:text-slate-400">{{ $p->nomor_surat_lengkap }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $p->tanggal_surat?->locale('id')->translatedFormat('j M Y') }}</p>
                                    @if($p->kaprodi)<p class="text-[10px] text-slate-500" title="Penandatangan"><x-icon name="pencil" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $p->kaprodi->nama }}</p>@endif
                                @elseif($p->status === 'ditolak' && $p->komentar)
                                    <p class="mt-1 text-[10px] text-rose-700 dark:text-rose-400 max-w-[200px]">{{ $p->komentar }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    @if($p->status !== 'disetujui')
                                        <button type="button" @click="modal = true; mode = 'disetujui'; action = '{{ route('bebas-prodi.verifikasi', $p) }}'; nama = @js($p->nama_mahasiswa)"
                                                class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold rounded-lg">Setujui</button>
                                    @endif
                                    @if($p->status !== 'ditolak')
                                        <button type="button" @click="modal = true; mode = 'ditolak'; action = '{{ route('bebas-prodi.verifikasi', $p) }}'; nama = @js($p->nama_mahasiswa)"
                                                class="px-2.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-[11px] font-bold rounded-lg">Tolak</button>
                                    @endif
                                    @if($p->status === 'disetujui')
                                        <a href="{{ route('bebas-prodi.cetak', $p) }}" target="_blank" class="px-2.5 py-1.5 bg-slate-800 dark:bg-slate-600 hover:bg-slate-900 text-white text-[11px] font-bold rounded-lg"><x-icon name="printer" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> Cetak</a>
                                    @endif
                                    @if($isSuperAdmin)
                                        <form method="POST" action="{{ route('bebas-prodi.destroy', $p) }}" onsubmit="return confirm('Hapus pengajuan Bebas Prodi {{ addslashes($p->nama_mahasiswa) }}? Berkas juga akan dihapus.')">
                                            @csrf @method('DELETE')
                                            <button class="px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-500 hover:text-rose-600 text-[11px] font-bold rounded-lg">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400 italic">Belum ada pengajuan Bebas Prodi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pengajuans->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-700">{{ $pengajuans->links() }}</div>
        @endif
    </div>

    {{-- Modal verifikasi --}}
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <form method="POST" :action="action" @click.away="modal = false"
              class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-md shadow-2xl border border-slate-100 dark:border-slate-700 p-6 space-y-4">
            @csrf
            <input type="hidden" name="status" :value="mode">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100" x-text="mode === 'disetujui' ? 'Setujui Bebas Prodi' : 'Tolak Pengajuan'"></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400" x-text="nama"></p>
            </div>

            <template x-if="mode === 'disetujui'">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Tanggal Surat</label>
                    <input type="date" name="tanggal_surat" value="{{ now()->timezone('Asia/Jakarta')->toDateString() }}"
                           class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                    <label class="block pt-1 text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Penandatangan</label>
                    <select name="kaprodi_id" class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                        <option value="">Otomatis — Kaprodi yang menjabat pada tanggal surat</option>
                        @foreach($kaprodis as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }} ({{ $k->masa_jabatan }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500">Nomor surat diterbitkan otomatis (berikutnya: <span class="font-mono">TI/BP/{{ $nextNomor }}</span>). Mahasiswa langsung bisa mencetak surat.</p>
                </div>
            </template>
            <template x-if="mode === 'ditolak'">
                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Catatan untuk mahasiswa <span class="text-rose-500">*</span></label>
                    <textarea name="komentar" rows="3" required placeholder="Contoh: Halaman pengesahan belum ditandatangani Dekan."
                              class="w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200"></textarea>
                </div>
            </template>

            <div class="flex justify-end gap-2">
                <button type="button" @click="modal = false" class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 rounded-xl border border-slate-300 dark:border-slate-600">Batal</button>
                <button type="submit" class="px-4 py-2 text-xs font-extrabold text-white rounded-xl"
                        :class="mode === 'disetujui' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'"
                        x-text="mode === 'disetujui' ? 'Setujui & Terbitkan Nomor' : 'Tolak'"></button>
            </div>
        </form>
    </div>

    @if($isSuperAdmin)
    {{-- Modal kelola Kaprodi --}}
    <div x-show="kaprodiModal" x-cloak class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto">
        <div @click.away="kaprodiModal = false" class="bg-white dark:bg-slate-800 rounded-2xl w-full max-w-2xl shadow-2xl border border-slate-100 dark:border-slate-700 my-6">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Kelola Kaprodi Penandatangan</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Surat ditandatangani secara elektronik (QR Code) atas nama Kaprodi yang menjabat pada tanggal surat. Surat yang sudah terbit tetap memakai Kaprodi saat itu.</p>
                </div>
                <button type="button" @click="kaprodiModal = false" class="w-8 h-8 rounded-full bg-slate-200/70 dark:bg-slate-700 text-slate-500 text-xs font-bold shrink-0"><x-icon name="x" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /></button>
            </div>

            <div class="p-6 space-y-4">
                @php $fieldCls = 'w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-xs bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200'; @endphp

                @foreach($kaprodis as $k)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-4" x-data="{ edit: false }">
                        <div class="flex flex-wrap gap-3 items-start justify-between">
                            <div class="flex gap-3 items-center">
                                <div>
                                    <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $k->nama }}
                                        @if($kaprodiSekarang && $kaprodiSekarang->id === $k->id)<span class="ml-1 px-1.5 py-0.5 rounded bg-emerald-600 text-white text-[9px] uppercase">Menjabat</span>@endif
                                    </p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $k->masa_jabatan }} · {{ $k->bebas_prodis_count }} surat</p>
                                </div>
                            </div>
                            <div class="flex gap-1.5">
                                <button type="button" @click="edit = !edit" class="px-2.5 py-1.5 bg-slate-800 dark:bg-slate-600 text-white text-[11px] font-bold rounded-lg">Ubah</button>
                                <form method="POST" action="{{ route('bebas-prodi.kaprodi.destroy', $k) }}" onsubmit="return confirm('Hapus Kaprodi {{ addslashes($k->nama) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="px-2.5 py-1.5 border border-slate-300 dark:border-slate-600 text-slate-500 hover:text-rose-600 text-[11px] font-bold rounded-lg">Hapus</button>
                                </form>
                            </div>
                        </div>
                        <form x-show="edit" x-cloak method="POST" action="{{ route('bebas-prodi.kaprodi.update', $k) }}" class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @csrf @method('PUT')
                            <div class="sm:col-span-2">
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Nama lengkap &amp; gelar</label>
                                <input type="text" name="nama" required value="{{ $k->nama }}" class="{{ $fieldCls }}">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Mulai menjabat</label>
                                <input type="date" name="mulai_menjabat" value="{{ $k->mulai_menjabat?->toDateString() }}" class="{{ $fieldCls }}">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Selesai menjabat <span class="font-normal text-slate-400">(kosongkan bila masih menjabat)</span></label>
                                <input type="date" name="selesai_menjabat" value="{{ $k->selesai_menjabat?->toDateString() }}" class="{{ $fieldCls }}">
                            </div>
                            <div class="sm:col-span-2 flex justify-end">
                                <button class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-lg">Simpan</button>
                            </div>
                        </form>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('bebas-prodi.kaprodi.store') }}" class="rounded-xl border-2 border-dashed border-teal-300 dark:border-teal-800 p-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @csrf
                    <p class="sm:col-span-2 text-xs font-extrabold text-teal-800 dark:text-teal-300">+ Tambah Kaprodi baru (pergantian jabatan)</p>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Nama lengkap &amp; gelar <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama" required value="{{ old('nama') }}" placeholder="Contoh: Dr. Nama Kaprodi, S.Kom., M.Kom" class="{{ $fieldCls }}">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Mulai menjabat <span class="text-rose-500">*</span></label>
                        <input type="date" name="mulai_menjabat" required value="{{ old('mulai_menjabat') }}" class="{{ $fieldCls }}">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Selesai menjabat</label>
                        <input type="date" name="selesai_menjabat" value="{{ old('selesai_menjabat') }}" class="{{ $fieldCls }}">
                    </div>
                    <p class="sm:col-span-2 text-[10px] text-slate-500">Tips: isi juga tanggal <strong>selesai menjabat</strong> pada Kaprodi sebelumnya.</p>
                    <div class="sm:col-span-2 flex justify-end">
                        <button class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold rounded-lg">Tambah Kaprodi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
</x-app-layout>
