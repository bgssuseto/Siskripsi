{{-- Riwayat pendaftaran versi kartu untuk layar HP (tabel tampil mulai md). Variabel: $sidangs --}}
<div class="md:hidden space-y-3">
    @foreach($sidangs as $i => $s)
        <article class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-900/40 p-4 space-y-3">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">#{{ $i + 1 }} · {{ $s->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : ($s->jenis_tugas_akhir === 'jurnal' ? 'Jurnal' : 'Sidang Skripsi') }}</p>
                    <div class="text-[11px]">{!! $s->getVerifikasiStatusHtml() !!}</div>
                </div>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-100 leading-snug break-words line-clamp-3">{{ $s->judul_skripsi }}</p>
            </div>
            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                <div class="min-w-0">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Tgl. daftar</dt>
                    <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $s->tanggal_pendaftaran ? \Carbon\Carbon::parse($s->tanggal_pendaftaran)->locale('id')->translatedFormat('d M Y') : '-' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Ruang</dt>
                    <dd class="font-semibold text-slate-700 dark:text-slate-200">{{ $s->ruang->kode_ruangan ?? '-' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">No. WA</dt>
                    <dd class="font-mono font-semibold text-slate-700 dark:text-slate-200 break-all">{{ $s->no_wa_aktif ?? '-' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Pembimbing utama</dt>
                    <dd class="font-semibold text-slate-700 dark:text-slate-200 break-words">{{ $s->pembimbingUtama->nama_dosen ?? '-' }}</dd>
                </div>
            </dl>
            @if($s->file_persyaratan)
                <div class="flex gap-2 pt-1">
                    <a href="{{ route('dokumen.show', [$s, 'persyaratan']) }}" target="_blank" class="ui-btn-secondary flex-1"><x-icon name="eye" class="w-4 h-4" /> Lihat berkas</a>
                    <a href="{{ route('dokumen.show', [$s, 'persyaratan']) }}?download=1" class="ui-btn-secondary"><x-icon name="download" class="w-4 h-4" /></a>
                </div>
            @elseif($s->bukti_pembayaran)
                <a href="{{ route('dokumen.show', [$s, 'bukti-pembayaran']) }}" target="_blank" class="ui-btn-secondary w-full"><x-icon name="document" class="w-4 h-4" /> Lihat bukti</a>
            @else
                <p class="text-xs italic text-slate-400">Berkas belum diunggah</p>
            @endif
        </article>
    @endforeach
</div>
