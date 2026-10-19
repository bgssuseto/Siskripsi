<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kesediaan Menguji - {{ $periode->nama_periode }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- Halaman ini selalu gelap; varian dark: pada komponen kalender ikut class "dark" di <html>. --}}
    <script>tailwind.config = { darkMode: 'class' };</script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen p-4 sm:p-6">
    <div class="max-w-2xl mx-auto w-full space-y-6">

        <!-- Header -->
        <div class="bg-white p-6 rounded-3xl border border-indigo-200 shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 align-middle"></span> Link Aktif
                    </span>
                    <span class="text-xs text-slate-500">Periode: {{ $periode->nama_periode }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Form Kesediaan Menguji</h1>
                <p class="text-xs text-indigo-700 mt-1">Isikan hari &amp; tanggal Anda bersedia menjadi penguji Sempro / Sidang Skripsi.</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach($activeWaves as $w)
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200">
                            Gelombang {{ $w->gelombang }} · {{ ucfirst($w->jenis) }} — {{ $w->tanggal_mulai?->format('d/m/Y') }} s/d {{ $w->tanggal_selesai?->format('d/m/Y') }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl p-4 text-sm font-semibold">
            <x-icon name="check-circle" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl p-4 text-sm font-semibold">
            <x-icon name="warning" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ session('error') }}
        </div>
        @endif
        @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl p-4 text-sm font-semibold space-y-1">
            @foreach($errors->all() as $err)
                <p><x-icon name="warning" class="inline-block w-[1.1em] h-[1.1em] align-[-0.2em] shrink-0" /> {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200 shadow-lg">
            <form method="POST" action="{{ route('public.kesediaan.store', $token) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Nama Dosen *</label>
                    <select name="dosen_id" required class="w-full text-sm p-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Nama Anda --</option>
                        @foreach($dosens as $d)
                            <option value="{{ $d->id }}" {{ old('dosen_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_dosen }} @if($d->nidn) (NIDN: {{ $d->nidn }}) @endif</option>
                        @endforeach
                    </select>
                </div>

                @if($activeWaves->count() > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Gelombang Ujian *</label>
                    <select name="wave_id" required class="w-full text-sm p-3 rounded-xl bg-white border border-slate-200 text-slate-900 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Gelombang --</option>
                        @foreach($activeWaves as $w)
                            <option value="{{ $w->id }}" {{ old('wave_id') == $w->id ? 'selected' : '' }}>Gelombang {{ $w->gelombang }} - {{ ucfirst($w->jenis) }}</option>
                        @endforeach
                    </select>
                </div>

                <x-kesediaan-date-picker
                    :selected="collect(old('slots', []))->pluck('tanggal')->filter()->unique()->values()->all()"
                    :keterangan="old('slots.0.keterangan', '')" />

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm rounded-2xl shadow-lg transition-all">
                    Simpan Kesediaan
                </button>
                @else
                {{-- Kesediaan WAJIB ditautkan ke gelombang + periode semester ini --
                     tanpa gelombang yang tersedia, form tidak bisa disubmit sama sekali. --}}
                <div class="bg-amber-50 border border-amber-200 text-amber-200 rounded-2xl p-4 text-xs leading-relaxed">
                    Belum ada Gelombang yang tersedia untuk periode <strong>{{ $periode->nama_periode }}</strong> saat ini. Hubungi Koordinator untuk menambahkan gelombang di menu Master Gelombang terlebih dahulu.
                </div>
                @endif
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-500">Sistem Informasi Tugas Akhir — Program Studi Teknik Informatika, Universitas Muria Kudus.</p>
    </div>
</body>
</html>
