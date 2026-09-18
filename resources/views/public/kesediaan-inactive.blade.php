<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kesediaan Menguji - Tidak Aktif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-800/80 rounded-3xl p-8 border border-slate-700/80 shadow-2xl text-center">
        <div class="w-16 h-16 rounded-full bg-amber-500/10 text-amber-300 flex items-center justify-center text-3xl mx-auto mb-4">
            🔒
        </div>
        <h1 class="text-xl font-extrabold text-white mb-2">Link Sedang Tidak Aktif</h1>
        <p class="text-sm text-slate-400 leading-relaxed">
            @if($periode)
                Form kesediaan menguji untuk periode <strong class="text-slate-200">{{ $periode->nama_periode }}</strong> saat ini tidak dalam jendela gelombang pendaftaran yang berjalan, sudah dikunci, atau disembunyikan oleh admin.
            @else
                Link ini tidak valid atau sudah tidak berlaku.
            @endif
        </p>
        <p class="text-xs text-slate-500 mt-4">Silakan hubungi Koordinator Skripsi/Tugas Akhir untuk informasi jadwal gelombang berikutnya.</p>
    </div>
</body>
</html>
