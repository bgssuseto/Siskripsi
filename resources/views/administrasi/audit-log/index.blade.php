<x-app-layout title="Administrasi - Riwayat Aktivitas">
    <x-slot:header>Riwayat Aktivitas</x-slot:header>

    <div class="space-y-6">

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-800 dark:text-slate-100 tracking-tight flex items-center gap-2.5">
                    <span class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </span>
                    Riwayat Aktivitas (Audit Log)
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Jejak perubahan jadwal, verifikasi pendaftaran, dan data dosen — siapa mengubah apa dan kapan.
                </p>
            </div>
        </div>

        <!-- Filter -->
        <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <form method="GET" action="{{ route('administrasi.audit-log.index') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama mahasiswa, dosen, pengguna..."
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Jenis Aksi</label>
                    <select name="action" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all">
                        <option value="">-- Semua Aksi --</option>
                        @foreach($actionOptions as $a)
                        <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ \App\Models\ActivityLog::actionLabel($a) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Pengguna</label>
                    <select name="user_id" class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all">
                        <option value="">-- Semua Pengguna --</option>
                        @foreach($userOptions as $u)
                        <option value="{{ $u->id }}" {{ (string) request('user_id') === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                    <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                           class="w-full px-3.5 py-2 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-200 focus:bg-white dark:focus:bg-slate-950 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-all">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-medium text-sm rounded-xl shadow-md transition-all">
                        Filter
                    </button>
                    @if(request()->anyFilled(['search','action','user_id','tanggal_mulai','tanggal_selesai']))
                    <a href="{{ route('administrasi.audit-log.index') }}"
                       class="px-3.5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium text-sm rounded-xl transition-all flex items-center justify-center" title="Reset Filter">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Log -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200/80 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 whitespace-nowrap">Waktu</th>
                            <th class="py-3.5 px-4">Pengguna</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                            <th class="py-3.5 px-4">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors align-top">
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400 text-xs">
                                {{ $log->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $log->user_name ?? 'Sistem' }}</div>
                                @if($log->user_role)
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ ucfirst(str_replace('_', ' ', $log->user_role)) }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $actionBadgeClass = match($log->action) {
                                        'jadwalkan', 'reschedule' => 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-300',
                                        'verifikasi' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300',
                                        'created' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                                        'updated' => 'bg-sky-50 dark:bg-sky-500/10 text-sky-700 dark:text-sky-300',
                                        'deleted' => 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300',
                                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $actionBadgeClass }}">
                                    {{ $log->action_label }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 max-w-xl">
                                {{ $log->description }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <p class="font-semibold text-slate-600 dark:text-slate-300">Belum ada riwayat aktivitas</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Aktivitas seperti plotting jadwal, verifikasi pendaftaran, dan perubahan data dosen akan tercatat di sini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="px-5 py-4 border-t border-slate-100 dark:border-slate-800">
                {{ $logs->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
