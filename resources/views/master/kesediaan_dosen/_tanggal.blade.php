{{-- Daftar tanggal kesediaan ringkas ("Sen, 9 Nov"); 6 pertama, sisanya lewat tombol "+N lainnya" (state `semua` dari <tr>). --}}
<div class="flex max-w-xl flex-wrap gap-1.5">
    @foreach($tanggal as $i => $t)
        <span @if($i >= 6) x-show="semua" x-cloak @endif
              title="{{ $t->translatedFormat('l, d F Y') }}"
              class="inline-flex items-center whitespace-nowrap rounded-md border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ $t->translatedFormat('D, j M') }}
        </span>
    @endforeach
    @if($tanggal->count() > 6)
        <button type="button" @click="semua = !semua"
                class="rounded-md px-2 py-0.5 text-xs font-semibold text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-500/10"
                x-text="semua ? 'Ringkas' : '+{{ $tanggal->count() - 6 }} lainnya'">+{{ $tanggal->count() - 6 }} lainnya</button>
    @endif
</div>
<p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ $tanggal->count() }} hari</p>
