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
<body class="bg-slate-100 text-slate-800 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-slate-200 shadow-sm text-center">
        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-700 flex items-center justify-center text-3xl mx-auto mb-4">
            <x-icon name="lock" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" />
        </div>
        <h1 class="text-xl font-extrabold text-slate-900 mb-2">Link Sedang Tidak Aktif</h1>
        <p class="text-sm text-slate-500 leading-relaxed">
            @if($periode)
                Form kesediaan menguji untuk periode <strong class="text-slate-700">{{ $periode->nama_periode }}</strong> saat ini sudah dikunci atau disembunyikan oleh admin.
            @else
                Link ini tidak valid atau sudah tidak berlaku.
            @endif
        </p>
        <p class="text-xs text-slate-500 mt-4">Silakan hubungi Koordinator Skripsi/Tugas Akhir untuk membuka kembali form ini.</p>
    </div>
</body>
</html>
