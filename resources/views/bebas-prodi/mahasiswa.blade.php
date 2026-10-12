<x-app-layout title="Bebas Prodi">
@php
    // Kolom ke-3: cara pengumpulan — 'pdf' (digabung di berkas), 'link' (input
    // link di formulir), 'cek' (konfirmasi checkbox di formulir).
    $dokumenWajib = [
        ['Halaman Persetujuan Skripsi', 'Ditandatangani dosen pembimbing', 'pdf'],
        ['Halaman Pengesahan Skripsi', 'Ditandatangani tim penguji, Ketua Progdi, dan Dekan', 'pdf'],
        ['Halaman Pernyataan Keaslian', 'Bermaterai, menyatakan skripsi bebas plagiasi', 'pdf'],
        ['Poster Skripsi', 'Sesuai Panduan Poster di halaman Panduan Skripsi. Upload di Google Drive, lalu isi link-nya pada formulir', 'link'],
        ['Berita Acara Sidang', 'Ditandatangani tim penguji saat sidang', 'pdf'],
        ['Program Full termasuk Database (jika ada)', 'Upload di Google Drive pribadi masing-masing mahasiswa, pastikan dapat diakses (share sebagai Editor/Publik), lalu isi link-nya pada formulir', 'link'],
        ['Scan Sertifikat ESQ/SAPAMABA & KW', 'Komputer, Bahasa Inggris & Kewirausahaan', 'pdf'],
        ['Scan Transkrip Nilai', 'Minimal lulus 144 SKS, tanpa nilai E, dan nilai D maksimal 10% dari total SKS', 'pdf'],
        ['Sertifikat Keahlian Bidang TI (jika ada)', 'Contoh: Cisco, Oracle, Microsoft, Jaringan, Pemrograman, dsb', 'pdf'],
        ['Link Profil LinkedIn Pribadi', 'Cantumkan link profil LinkedIn pribadi Anda pada formulir — dan follow LinkedIn resmi UMK', 'link'],
        ['Follow Instagram @ti.umk', 'Wajib follow sebelum submit form', 'cek'],
    ];
    $syaratJalur = [
        'sidang' => ['label' => 'Jalur Sidang', 'items' => ['File Laporan Skripsi lengkap', 'File PPT / materi presentasi Sidang', 'Scan buku konsultasi/bimbingan skripsi']],
        'jurnal' => ['label' => 'Jalur Jurnal', 'items' => ['File Artikel Jurnal', 'Link artikel jurnal yang telah terbit', 'Scan buku konsultasi Skripsi']],
    ];
    $jalur = $kelayakan['jalur'];
    $bisaIsi = $kelayakan['eligible'] && (!$pengajuan || $pengajuan->status === 'ditolak');
    $inputCls = 'w-full px-3.5 py-2 border border-slate-300 dark:border-slate-600 rounded-xl text-xs focus:outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-500/20 transition-all text-slate-800 dark:text-slate-200 font-medium bg-white dark:bg-slate-700 placeholder-slate-400';
@endphp
<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
    <div class="bg-gradient-to-r from-teal-900 via-slate-900 to-emerald-950 p-6 rounded-2xl text-white shadow-xl flex flex-wrap gap-3 justify-between items-center">
        <div>
            <h1 class="text-2xl font-extrabold mb-1">Bebas Prodi</h1>
            <p class="text-xs text-teal-200">Ajukan Surat Keterangan Bebas Prodi setelah lulus Seminar Proposal &amp; Skripsi.</p>
        </div>
        <span class="px-3.5 py-1.5 bg-teal-500/30 border border-teal-400/30 text-teal-100 text-xs font-bold rounded-xl">Surat Bebas Prodi</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Kiri: kelayakan & status --}}
        <div class="lg:col-span-1 space-y-6 h-fit">
            <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-5 border border-slate-200/80 dark:border-slate-700">
                <h2 class="text-[11px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Syarat Kelulusan</h2>
                <ul class="space-y-2 text-xs font-bold">
                    @foreach(['lulus_sempro' => 'Lulus Seminar Proposal', 'lulus_skripsi' => 'Lulus Sidang Skripsi / Jurnal'] as $key => $label)
                        <li class="flex items-center gap-2.5">
                            @if($kelayakan[$key])
                                <span class="w-5 h-5 rounded-full bg-emerald-500 text-white flex items-center justify-center text-[11px]">✓</span>
                                <span class="text-slate-800 dark:text-slate-200">{{ $label }}</span>
                            @else
                                <span class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-500 flex items-center justify-center text-[11px]">✕</span>
                                <span class="text-slate-400">{{ $label }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if($kelayakan['bypass'])
                    <p class="mt-3 px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-[11px] font-bold text-amber-800 dark:text-amber-300">🧪 Akun testing — akses Bebas Prodi dibuka tanpa syarat kelulusan.</p>
                @endif
                @if($jalur)
                    <p class="mt-3 text-[11px] text-slate-500 dark:text-slate-400">Jalur skripsi Anda: <strong class="text-slate-800 dark:text-slate-200">{{ $syaratJalur[$jalur]['label'] }}</strong></p>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-5 border border-slate-200/80 dark:border-slate-700">
                <h2 class="text-[11px] font-extrabold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-3">Status Pengajuan</h2>
                @if(!$kelayakan['eligible'])
                    <div class="bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl p-3 text-xs text-slate-700 dark:text-slate-300 font-bold flex gap-2">
                        <span>🔒</span>
                        <span>Pendaftaran Bebas Prodi belum terbuka. Anda harus dinyatakan <strong>lulus Seminar Proposal dan Skripsi</strong> terlebih dahulu.</span>
                    </div>
                @elseif(!$pengajuan)
                    <div class="bg-teal-50 dark:bg-teal-950/30 border border-teal-200 dark:border-teal-800 rounded-xl p-3 text-xs text-teal-900 dark:text-teal-300 font-bold flex gap-2">
                        <span>📝</span><span>Pendaftaran terbuka. Silakan lengkapi formulir pengajuan.</span>
                    </div>
                @elseif($pengajuan->status === 'menunggu')
                    <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-xs text-amber-900 dark:text-amber-300 font-bold flex gap-2">
                        <span>⏳</span><span>Pengajuan terkirim {{ $pengajuan->submitted_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} dan sedang diverifikasi admin.</span>
                    </div>
                @elseif($pengajuan->status === 'ditolak')
                    <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs text-rose-900 dark:text-rose-300 space-y-1">
                        <p class="font-extrabold">✕ Pengajuan perlu direvisi</p>
                        @if($pengajuan->komentar)<p class="font-medium">Catatan admin: {{ $pengajuan->komentar }}</p>@endif
                        <p class="font-medium">Perbaiki berkas lalu kirim ulang lewat formulir.</p>
                    </div>
                @else
                    <div class="bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl p-3 space-y-3">
                        <p class="text-xs text-emerald-900 dark:text-emerald-300 font-extrabold">✅ Pengajuan disetujui</p>
                        <p class="text-[11px] text-emerald-800 dark:text-emerald-400">No. Surat: <strong class="font-mono">{{ $pengajuan->nomor_surat_lengkap }}</strong><br>Tanggal: {{ $pengajuan->tanggal_surat?->locale('id')->translatedFormat('j F Y') }}</p>
                        <a href="{{ route('mahasiswa.bebas-prodi.cetak') }}" target="_blank" class="w-full px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                            <span>🖨️</span> Cetak Surat Bebas Prodi
                        </a>
                    </div>
                @endif

                @if($pengajuan)
                    <a href="{{ route('bebas-prodi.berkas', $pengajuan) }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-[11px] font-bold text-teal-700 dark:text-teal-400 hover:underline">📄 Lihat berkas yang dikirim</a>
                @endif
            </div>
        </div>

        {{-- Kanan: syarat & ketentuan --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm p-6 border border-slate-200/80 dark:border-slate-700">
            <div class="flex items-center gap-3 pb-3 border-b border-slate-100 dark:border-slate-700 mb-4">
                <div class="w-9 h-9 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center text-base shrink-0 border border-teal-200 dark:border-teal-800">📋</div>
                <div>
                    <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Syarat &amp; Ketentuan Bebas Prodi</h2>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Dokumen wajib disiapkan:</p>
                </div>
            </div>

            <ol class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                @foreach($dokumenWajib as $i => [$judul, $ket, $cara])
                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-lg bg-teal-600 text-white text-[11px] font-extrabold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                        <div>
                            <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200">{{ $judul }}
                                @if($cara === 'link')<span class="ml-1 px-1.5 py-0.5 rounded bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300 text-[9px] font-extrabold uppercase align-middle">Isi Link</span>
                                @elseif($cara === 'cek')<span class="ml-1 px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 text-[9px] font-extrabold uppercase align-middle">Centang di Form</span>
                                @else<span class="ml-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300 text-[9px] font-extrabold uppercase align-middle">PDF</span>@endif
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">{{ $ket }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <h3 class="mt-6 mb-3 text-xs font-extrabold text-slate-900 dark:text-slate-100">Syarat Khusus Sesuai Jalur Skripsi</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($syaratJalur as $key => $sj)
                    <div class="rounded-xl p-4 border {{ $jalur === $key ? 'border-teal-400 bg-teal-50/70 dark:bg-teal-950/30 dark:border-teal-700' : 'border-slate-200 dark:border-slate-700' }}">
                        <p class="text-xs font-extrabold text-slate-800 dark:text-slate-200 mb-2">{{ $sj['label'] }} @if($jalur === $key)<span class="ml-1 px-1.5 py-0.5 rounded bg-teal-600 text-white text-[9px] uppercase">Jalur Anda</span>@endif</p>
                        <ul class="space-y-1 text-[11px] text-slate-600 dark:text-slate-400 list-disc pl-4">
                            @foreach($sj['items'] as $item)<li>{{ $item }}</li>@endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <p class="mt-4 text-[11px] text-slate-500 dark:text-slate-400">Poin <strong>4 (Poster)</strong>, <strong>6 (Program)</strong>, dan <strong>10 (LinkedIn)</strong> diisi berupa <strong>link</strong> pada formulir. Dokumen lainnya (termasuk syarat khusus jalur) digabung menjadi <strong>1 file PDF (maks. 4 MB)</strong>. Format nama file: <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-teal-700 dark:text-teal-300 font-mono">NIM_NAMA_BEBASPRODI.pdf</code></p>
        </div>
    </div>

    {{-- Formulir pengajuan --}}
    @if($bisaIsi)
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/50">
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">{{ $pengajuan ? 'Revisi Pengajuan Bebas Prodi' : 'Formulir Pengajuan Bebas Prodi' }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Lengkapi data berikut dengan benar.</p>
        </div>

        <form method="POST" action="{{ route('mahasiswa.bebas-prodi.store') }}" enctype="multipart/form-data" class="p-6 space-y-4"
              x-data="{ sending: false }" @submit="sending = true">
            @csrf

            @if($errors->any())
                <div class="bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl p-3 text-xs text-rose-800 dark:text-rose-300">
                    <ul class="list-disc pl-4 space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">NIM</label>
                    <input type="text" value="{{ $user->nim }}" readonly class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Nama Mahasiswa</label>
                    <input type="text" value="{{ $user->name }}" readonly class="w-full px-3.5 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-600 dark:text-slate-300 font-semibold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Link Google Drive Poster Skripsi <span class="text-rose-500">*</span></label>
                    <input type="url" name="link_poster" required value="{{ old('link_poster', $pengajuan->link_poster ?? '') }}" placeholder="https://drive.google.com/..." class="{{ $inputCls }}">
                    <p class="text-[10px] text-slate-400 mt-1">Pastikan dapat diakses (share sebagai Publik).</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Link Profil LinkedIn Pribadi <span class="text-rose-500">*</span></label>
                    <input type="url" name="link_linkedin" required value="{{ old('link_linkedin', $pengajuan->link_linkedin ?? '') }}" placeholder="https://www.linkedin.com/in/username" class="{{ $inputCls }}">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Link Program Aplikasi &amp; Database <span class="text-[9px] text-slate-400 font-normal normal-case">(jika ada)</span></label>
                    <input type="url" name="link_program" value="{{ old('link_program', $pengajuan->link_program ?? '') }}" placeholder="https://drive.google.com/..." class="{{ $inputCls }}">
                    <p class="text-[10px] text-slate-400 mt-1">Pastikan dapat diakses (share sebagai Editor/Publik).</p>
                </div>
                @if($jalur === 'jurnal')
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Link Artikel Jurnal yang Telah Terbit <span class="text-rose-500">*</span></label>
                    <input type="url" name="link_jurnal" required value="{{ old('link_jurnal', $pengajuan->link_jurnal ?? '') }}" placeholder="https://..." class="{{ $inputCls }}">
                </div>
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider mb-1.5">Berkas Bebas Prodi — dokumen selain Poster, Program &amp; LinkedIn (1 file PDF, maks. 4 MB) @unless($pengajuan)<span class="text-rose-500">*</span>@endunless</label>
                <input type="file" name="file_berkas" accept="application/pdf" @unless($pengajuan) required @endunless
                       class="w-full text-xs text-slate-600 dark:text-slate-300 file:mr-3 file:px-3 file:py-2 file:rounded-lg file:border-0 file:bg-teal-600 file:text-white file:font-bold file:cursor-pointer border border-slate-300 dark:border-slate-600 rounded-xl p-1.5 bg-white dark:bg-slate-700"
                       @change="if ($event.target.files[0] && $event.target.files[0].size > 4 * 1024 * 1024) { alert('Ukuran file melebihi 4 MB. Silakan kompres PDF terlebih dahulu.'); $event.target.value = ''; }">
                @if($pengajuan)<p class="text-[10px] text-slate-400 mt-1">Kosongkan jika tidak ingin mengganti berkas sebelumnya.</p>@endif
            </div>

            <div class="space-y-2 p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-700">
                <label class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                    <input type="checkbox" name="follow_instagram" value="1" required @checked(old('follow_instagram')) class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <span>Saya sudah follow Instagram <a href="https://www.instagram.com/ti.umk/" target="_blank" rel="noopener" class="text-teal-700 dark:text-teal-400 font-bold hover:underline">@ti.umk</a></span>
                </label>
                <label class="flex items-start gap-2.5 text-xs text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                    <input type="checkbox" name="follow_linkedin" value="1" required @checked(old('follow_linkedin')) class="mt-0.5 rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                    <span>Saya sudah follow LinkedIn resmi Universitas Muria Kudus</span>
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" :disabled="sending" style="background-color:#0d9488;color:#fff" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white text-xs font-extrabold rounded-xl shadow-md transition-all">
                    <span x-show="!sending">{{ $pengajuan ? 'Kirim Ulang Pengajuan' : 'Kirim Pengajuan' }}</span>
                    <span x-show="sending" x-cloak>Mengirim...</span>
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
</x-app-layout>
