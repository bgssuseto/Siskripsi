{{--
    Tampilan kartu jadwal untuk layar HP (tabel disembunyikan di bawah md).
    Variabel: $schedules, $dosen, $aksen ('violet' | 'indigo'), $judulKosong
--}}
@php
    $chipJam = $aksen === 'violet'
        ? 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30'
        : 'bg-indigo-50 text-indigo-700 ring-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:ring-indigo-500/30';
@endphp
<div class="md:hidden space-y-3 mb-8">
    @if(!$dosen)
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 text-center">
            <h3 class="font-extrabold text-slate-800 dark:text-slate-200">Akun Belum Terhubung</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hubungkan akun Anda dengan Master Dosen untuk melihat jadwal.</p>
        </div>
    @else
        @forelse($schedules as $s)
            @php $peran = \App\Support\PeranDosen::untuk($s, $dosen->id); @endphp
            <article class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-4 space-y-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    @if($s->tanggal)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold ring-1 ring-inset {{ $chipJam }}">
                            <x-icon name="calendar" class="w-3.5 h-3.5" /> {{ $s->tanggal->locale('id')->translatedFormat('D, d M Y') }}
                        </span>
                        @if($s->jam)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold ring-1 ring-inset {{ $chipJam }}"><x-icon name="clock" class="w-3.5 h-3.5" /> {{ $s->jam }}</span>
                        @endif
                    @else
                        <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30">Belum terjadwal</span>
                    @endif
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"><x-icon name="map-pin" class="w-3.5 h-3.5" /> {{ $s->ruang?->kode_ruangan ?? '—' }}</span>
                </div>
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 dark:text-slate-100 leading-snug break-words">{{ $s->nama_mahasiswa }}</p>
                    <p class="text-[11px] font-mono font-semibold text-slate-500 dark:text-slate-400">NIM {{ $s->nim }}</p>
                    <p class="mt-1.5 text-xs text-slate-600 dark:text-slate-300 leading-relaxed line-clamp-3 break-words">{{ $s->judul_skripsi }}</p>
                </div>
                <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-2">
                    <div class="text-[11px]">{!! $s->getJadwalStatusHtml() !!}</div>
                    @if($peran)
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-0.5">Peran Anda</span>
                            @foreach($peran as $r)
                                <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-md {{ str_contains($r, 'Pembimbing') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30' : 'bg-violet-50 text-violet-700 ring-1 ring-inset ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30' }}">{{ $r }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center">
                <h3 class="font-extrabold text-slate-800 dark:text-slate-200">{{ $judulKosong }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tidak ditemukan jadwal yang melibatkan Anda saat ini.</p>
            </div>
        @endforelse
    @endif
</div>
