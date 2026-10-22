<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light dark">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Verifikasi Undangan Menguji — TI UMK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-start sm:items-center justify-center p-4 dark:text-slate-200 dark:bg-slate-950">
    <div class="max-w-2xl w-full my-6">
        <div class="flex items-center gap-3 mb-4 px-1">
            <img src="{{ asset('images/logo-ti-umk-icon.png') }}" alt="TI UMK" class="w-10 h-10 object-contain">
            <div>
                <p class="text-sm font-extrabold text-slate-900 leading-tight dark:text-slate-100">Program Studi Teknik Informatika</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">Fakultas Teknik · Universitas Muria Kudus</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden dark:bg-slate-800/80 dark:border-slate-700">
            @if($ttd)
                <div class="bg-emerald-600 text-white p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h1 class="text-xl font-extrabold tracking-wide">UNDANGAN ASLI &amp; SAH</h1>
                    <p class="text-sm text-emerald-50 mt-1">Ditandatangani secara elektronik oleh Koordinator Skripsi Prodi Teknik Informatika.</p>
                </div>

                <div class="p-5 sm:p-6 space-y-5">
                    <dl class="divide-y divide-slate-100 text-sm border border-slate-200 rounded-2xl dark:divide-slate-700 dark:border-slate-700">
                        @foreach([
                            'Jenis Dokumen'      => 'Undangan Menguji ' . $ttd->jenis_label,
                            'Kepada'             => $ttd->dosen?->nama_dosen ?? '-',
                            'Periode'            => $ttd->nama_periode . ($ttd->gelombang ? ' · Gelombang ' . $ttd->gelombang : ''),
                            'Jumlah Mahasiswa'   => $ttd->jumlah_mahasiswa . ' mahasiswa',
                            'Ditandatangani'     => ($ttd->nama_penandatangan ?? '-') . ' (Koordinator Skripsi/Tugas Akhir)',
                            'NIDN Penandatangan' => $ttd->nidn_penandatangan ?: '-',
                            'Waktu Tanda Tangan' => $ttd->created_at?->timezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') . ' WIB',
                        ] as $label => $value)
                            <div class="flex flex-col sm:flex-row sm:gap-4 px-4 py-2.5">
                                <dt class="sm:w-44 shrink-0 text-xs font-bold text-slate-500 uppercase tracking-wider sm:pt-0.5 dark:text-slate-400">{{ $label }}</dt>
                                <dd class="font-semibold text-slate-900 break-words dark:text-slate-100">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if(!empty($ttd->rincian))
                        <div>
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 dark:text-slate-400">Jadwal pada undangan ini</p>
                            <div class="border border-slate-200 rounded-2xl overflow-hidden dark:border-slate-700">
                                <ul class="divide-y divide-slate-100 dark:divide-slate-700">
                                    @foreach($ttd->rincian as $r)
                                        <li class="px-4 py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 text-sm">
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900 dark:text-slate-100 break-words">{{ $r['nama'] ?? '-' }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $r['nim'] ?? '' }}@if(!empty($r['peran'])) · {{ $r['peran'] }}@endif</p>
                                            </div>
                                            <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 sm:text-right shrink-0">
                                                {{ !empty($r['tanggal']) ? \Carbon\Carbon::parse($r['tanggal'])->locale('id')->translatedFormat('l, j M Y') : 'Belum dijadwalkan' }}
                                                @if(!empty($r['jam'])) · {{ $r['jam'] }}@endif
                                                @if(!empty($r['ruang'])) · {{ $r['ruang'] }}@endif
                                            </p>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <p class="text-[11px] text-slate-400 text-center">Pastikan isi undangan yang Anda terima sama dengan data di atas. Bila jadwal berubah, undangan baru akan memiliki QR yang berbeda.</p>
                </div>
            @else
                <div class="bg-rose-600 text-white p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </div>
                    <h1 class="text-xl font-extrabold tracking-wide">DOKUMEN TIDAK TERDAFTAR</h1>
                </div>
                <div class="p-6 text-sm text-slate-600 leading-relaxed space-y-3 dark:text-slate-400">
                    <p>Kode verifikasi ini <strong>tidak ditemukan</strong> dalam sistem Program Studi Teknik Informatika UMK. Undangan yang Anda pindai kemungkinan <strong>bukan dokumen asli</strong>.</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Hubungi Program Studi Teknik Informatika UMK (ti@umk.ac.id) untuk konfirmasi.</p>
                </div>
            @endif
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-4">&copy; {{ date('Y') }} Sistem Informasi Skripsi TI — Universitas Muria Kudus</p>
    </div>
</body>
</html>
