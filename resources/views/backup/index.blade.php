<x-app-layout title="Backup & Restore Database">
<div class="max-w-5xl mx-auto p-4 sm:p-6 space-y-6" x-data="{ restoreModal: false, confirmText: '' }">

    <!-- Header -->
    <div class="relative overflow-hidden rounded-3xl bg-slate-800 dark:bg-slate-900 p-6 sm:p-7 text-white shadow-xl border border-slate-700">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-[11px] font-extrabold border border-amber-400/30 mb-2.5">
                <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                Khusus Super Admin
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">Backup &amp; Restore Database</h1>
            <p class="text-xs text-slate-300 mt-1 max-w-2xl leading-relaxed">
                Buat salinan cadangan seluruh data sistem secara berkala, dan simpan file-nya di tempat aman
                di luar server. Fitur restore akan MENIMPA seluruh data saat ini — gunakan dengan sangat hati-hati.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-xs font-bold text-emerald-800 dark:text-emerald-300 flex items-center gap-2">
            <span>✅</span> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 rounded-2xl text-xs font-bold text-rose-800 dark:text-rose-300 flex items-center gap-2">
            <span>⚠️</span> {{ session('error') }}
        </div>
    @endif

    <!-- Actions -->
    <div class="flex flex-col sm:flex-row gap-3">
        <form method="POST" action="{{ route('backup.create') }}" class="flex-1">
            @csrf
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm rounded-2xl shadow-lg shadow-indigo-600/20 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
                Buat Backup Baru Sekarang
            </button>
        </form>
        <button type="button" @click="restoreModal = true" class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-white dark:bg-slate-900 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-700 dark:text-rose-400 font-extrabold text-sm rounded-2xl border-2 border-rose-200 dark:border-rose-800 transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Restore dari File Backup
        </button>
    </div>

    <!-- List of backups -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 dark:border-slate-800">
            <h2 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Riwayat File Backup</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $files->count() }} file backup tersimpan di server.</p>
        </div>
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($files as $file)
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-100 truncate font-mono">{{ $file['name'] }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                {{ $file['created_at']->locale('id')->isoFormat('dddd, D MMMM Y, HH:mm') }} WIB &middot; {{ number_format($file['size'] / 1024, 0) }} KB
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="{{ route('backup.download', $file['name']) }}" title="Unduh"
                           class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('backup.destroy', $file['name']) }}" onsubmit="return confirm('Hapus file backup {{ $file['name'] }}? Tindakan ini tidak dapat dibatalkan.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Hapus" class="inline-flex items-center justify-center w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-slate-400">
                    <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl flex items-center justify-center mx-auto mb-2 text-xl">🗄️</div>
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Belum ada file backup. Klik "Buat Backup Baru" untuk memulai.</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Restore Modal -->
    <div x-show="restoreModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm">
        <div @click.away="restoreModal = false" class="bg-white dark:bg-slate-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-800 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shrink-0">⚠️</div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-slate-100">Restore Database</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Tindakan ini akan MENIMPA seluruh data saat ini.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('backup.restore') }}" enctype="multipart/form-data" class="space-y-3"
                  onsubmit="return confirm('Anda benar-benar yakin? Semua data saat ini akan diganti dengan isi file backup ini.');">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">Pilih File Backup (.sql)</label>
                    <input type="file" name="sql_file" accept=".sql,.txt" required
                           class="w-full text-xs file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:bg-indigo-50 dark:file:bg-indigo-950/50 file:text-indigo-700 dark:file:text-indigo-300 file:font-bold border border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1.5">
                        Ketik <span class="font-mono bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 px-1.5 py-0.5 rounded">RESTORE</span> untuk mengonfirmasi
                    </label>
                    <input type="text" name="confirm" x-model="confirmText" required autocomplete="off"
                           class="w-full px-3 py-2.5 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold bg-white dark:bg-slate-950 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-rose-500/30 focus:border-rose-500">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="restoreModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs">Batal</button>
                    <button type="submit" :disabled="confirmText !== 'RESTORE'" :class="confirmText === 'RESTORE' ? 'bg-rose-600 hover:bg-rose-700 cursor-pointer' : 'bg-rose-300 cursor-not-allowed'" class="px-4 py-2.5 rounded-xl text-white font-bold text-xs transition-all">Restore Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
</x-app-layout>
