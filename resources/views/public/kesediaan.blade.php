<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kesediaan Menguji - {{ $periode->nama_periode }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen p-4 sm:p-6">
    <div class="max-w-2xl mx-auto w-full space-y-6">

        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-900 via-slate-900 to-indigo-950 p-6 rounded-3xl border border-indigo-500/30 shadow-2xl relative overflow-hidden">
            <div class="absolute -top-12 -right-12 w-44 h-44 bg-indigo-500/10 rounded-full blur-2xl"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-3 flex-wrap">
                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 text-xs font-bold rounded-full border border-emerald-400/30">
                        🟢 Link Aktif
                    </span>
                    <span class="text-xs text-slate-400">Periode: {{ $periode->nama_periode }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Form Kesediaan Menguji</h1>
                <p class="text-xs text-indigo-200 mt-1">Isikan hari &amp; tanggal Anda bersedia menjadi penguji Sempro / Sidang Skripsi.</p>
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach($activeWaves as $w)
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-indigo-500/20 text-indigo-200 border border-indigo-400/30">
                            Gelombang {{ $w->gelombang }} · {{ ucfirst($w->jenis) }} — {{ $w->tanggal_mulai?->format('d/m/Y') }} s/d {{ $w->tanggal_selesai?->format('d/m/Y') }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-400/30 text-emerald-300 rounded-2xl p-4 text-sm font-semibold">
            ✅ {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="bg-rose-500/10 border border-rose-400/30 text-rose-300 rounded-2xl p-4 text-sm font-semibold">
            ⚠️ {{ session('error') }}
        </div>
        @endif
        @if($errors->any())
        <div class="bg-rose-500/10 border border-rose-400/30 text-rose-300 rounded-2xl p-4 text-sm font-semibold space-y-1">
            @foreach($errors->all() as $err)
                <p>⚠️ {{ $err }}</p>
            @endforeach
        </div>
        @endif

        <!-- Form Card -->
        <div class="bg-slate-800/80 rounded-3xl p-5 sm:p-6 border border-slate-700/80 shadow-lg"
             x-data="{
                slots: [{ tanggal: '{{ now()->format('Y-m-d') }}', keterangan: '' }],
                addSlot() { this.slots.push({ tanggal: '{{ now()->format('Y-m-d') }}', keterangan: '' }); },
                removeSlot(i) { if (this.slots.length > 1) this.slots.splice(i, 1); }
             }">
            <form method="POST" action="{{ route('public.kesediaan.store', $token) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Nama Dosen *</label>
                    <select name="dosen_id" required class="w-full text-sm p-3 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">-- Pilih Nama Anda --</option>
                        @foreach($dosens as $d)
                            <option value="{{ $d->id }}" {{ old('dosen_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_dosen }} @if($d->nidn) (NIDN: {{ $d->nidn }}) @endif</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1.5">Gelombang Ujian *</label>
                    <select name="wave_id" required class="w-full text-sm p-3 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach($activeWaves as $w)
                            <option value="{{ $w->id }}" {{ old('wave_id') == $w->id ? 'selected' : '' }}>Gelombang {{ $w->gelombang }} - {{ ucfirst($w->jenis) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-extrabold text-slate-300 uppercase tracking-wider">Hari &amp; Tanggal Ketersediaan</label>
                        <button type="button" @click="addSlot()" class="text-xs font-bold text-indigo-300 hover:text-indigo-200 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-400/30 px-3 py-1.5 rounded-xl transition-colors">
                            + Tambah Tanggal
                        </button>
                    </div>

                    <template x-for="(slot, index) in slots" :key="index">
                        <div class="bg-slate-900/60 border border-slate-700 rounded-2xl p-4 mb-3 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-extrabold text-indigo-300 bg-indigo-500/10 px-2.5 py-0.5 rounded-lg" x-text="'Tanggal ' + (index + 1)"></span>
                                <button type="button" x-show="slots.length > 1" @click="removeSlot(index)" class="text-rose-400 hover:text-rose-300 text-xs font-bold">Hapus</button>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">Tanggal *</label>
                                <input type="date" :name="'slots['+index+'][tanggal]'" x-model="slot.tanggal" required class="w-full text-sm p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">Catatan (Opsional)</label>
                                <input type="text" :name="'slots['+index+'][keterangan]'" x-model="slot.keterangan" placeholder="Contoh: bisa luring/online" class="w-full text-sm p-2.5 rounded-xl bg-slate-900 border border-slate-700 text-slate-100 placeholder-slate-500 focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                    </template>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm rounded-2xl shadow-lg transition-all">
                    Simpan Kesediaan
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-500">Sistem Informasi Tugas Akhir — Program Studi Teknik Informatika, Universitas Muria Kudus.</p>
    </div>
</body>
</html>
