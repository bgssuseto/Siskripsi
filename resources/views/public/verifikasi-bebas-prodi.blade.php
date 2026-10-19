<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light dark">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi Surat Bebas Prodi — TI UMK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-start sm:items-center justify-center p-4 dark:text-slate-200 dark:bg-slate-950">
    <div class="max-w-lg w-full my-6">
        <div class="flex items-center gap-3 mb-4 px-1">
            <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI UMK" class="w-10 h-10 object-contain">
            <div>
                <p class="text-sm font-extrabold text-slate-900 leading-tight dark:text-slate-100">Program Studi Teknik Informatika</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Fakultas Teknik · Universitas Muria Kudus</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden dark:bg-slate-800/80 dark:border-slate-700">
            @if($sah)
                <div class="bg-emerald-600 text-white p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h1 class="text-xl font-extrabold tracking-wide">DOKUMEN ASLI &amp; SAH</h1>
                    <p class="text-sm text-emerald-50 mt-1">Surat ini terdaftar resmi dan sah digunakan untuk pemberkasan.</p>
                </div>

                <div class="p-6 space-y-5">
                    <p class="text-sm text-slate-600 leading-relaxed dark:text-slate-400">
                        Surat Keterangan Bebas Prodi berikut diterbitkan oleh Program Studi Teknik Informatika Universitas Muria Kudus
                        dan telah <strong>ditandatangani secara elektronik</strong> oleh Ketua Program Studi.
                    </p>

                    <dl class="divide-y divide-slate-100 text-sm border border-slate-200 rounded-2xl dark:divide-slate-700 dark:border-slate-700">
                        @foreach([
                            'Jenis Dokumen'    => 'Surat Keterangan Bebas Prodi',
                            'Nomor Surat'      => $surat->nomor_surat_lengkap,
                            'Nama Mahasiswa'   => $surat->nama_mahasiswa,
                            'NIM'              => $surat->nim,
                            'Program Studi'    => 'Teknik Informatika',
                            'Tanggal Surat'    => $surat->tanggal_surat?->locale('id')->translatedFormat('j F Y'),
                            'Ditandatangani'   => ($surat->kaprodi?->nama ?? '-') . ' (Ka. Prodi Teknik Informatika)',
                            'Waktu Tanda Tangan' => $surat->verified_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') . ' WIB',
                        ] as $label => $value)
                            <div class="flex flex-col sm:flex-row sm:gap-4 px-4 py-2.5">
                                <dt class="sm:w-40 shrink-0 text-xs font-bold text-slate-500 uppercase tracking-wider sm:pt-0.5 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="font-semibold text-slate-900 break-words dark:text-slate-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <a href="{{ route('verifikasi.bebas-prodi.surat', $kode) }}" target="_blank"
                       class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Lihat Surat yang Ditandatangani
                    </a>
                    <p class="text-[11px] text-slate-400 text-center">Pastikan isi surat yang Anda terima sama persis dengan surat pada tautan di atas.</p>
                </div>
            @else
                <div class="bg-rose-600 text-white p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h1 class="text-xl font-extrabold tracking-wide">{{ $surat ? 'SURAT TIDAK BERLAKU' : 'DOKUMEN TIDAK TERDAFTAR' }}</h1>
                </div>
                <div class="p-6 text-sm text-slate-600 leading-relaxed space-y-3 dark:text-slate-400">
                    @if($surat)
                        <p>Surat Keterangan Bebas Prodi atas nama <strong>{{ $surat->nama_mahasiswa }}</strong> ({{ $surat->nim }}) dengan kode ini <strong>telah dibatalkan</strong> oleh Program Studi dan tidak sah digunakan untuk pemberkasan.</p>
                    @else
                        <p>Kode verifikasi ini <strong>tidak ditemukan</strong> dalam sistem Program Studi Teknik Informatika UMK. Dokumen yang Anda pindai kemungkinan <strong>bukan surat asli</strong>.</p>
                    @endif
                    <p class="text-xs text-slate-500 dark:text-slate-400">Hubungi Program Studi Teknik Informatika UMK (ti@umk.ac.id) untuk konfirmasi.</p>
                </div>
            @endif
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-4">&copy; {{ date('Y') }} Sistem Informasi Skripsi TI — Universitas Muria Kudus</p>
    </div>
</body>
</html>
