@props(['form'])

{{-- Verifikasi keamanan: soal hitungan berupa gambar (jawaban hanya di session). --}}
<div x-data="{ src: '{{ route('captcha.image', $form) }}?t={{ now()->timestamp }}', muat() { this.src = '{{ route('captcha.image', $form) }}?t=' + Date.now(); this.$refs.jawaban.value = ''; this.$refs.jawaban.focus(); } }">
    <label for="captcha_input" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-2">Verifikasi Keamanan</label>
    <div class="flex items-center gap-2">
        <img :src="src" src="{{ route('captcha.image', $form) }}?t={{ now()->timestamp }}" alt="Soal hitungan verifikasi keamanan"
             width="150" height="52" class="shrink-0 w-[120px] h-auto sm:w-[150px] rounded-xl border border-slate-200 dark:border-slate-700 select-none" draggable="false">
        <button type="button" @click="muat()" title="Ganti soal" aria-label="Ganti soal"
                class="shrink-0 w-11 h-11 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:text-indigo-600 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
        <input id="captcha_input" name="captcha_input" x-ref="jawaban" type="text" inputmode="numeric" pattern="-?[0-9]*" required autocomplete="off"
               class="min-w-0 flex-1 px-4 py-3 bg-slate-100 dark:bg-slate-800 border border-transparent rounded-xl text-gray-900 dark:text-slate-100 placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:bg-white dark:focus:bg-slate-900 focus:border-indigo-500 transition-all duration-200 @error('captcha_input') bg-red-50 dark:bg-red-950/40 focus:border-red-500 focus:ring-red-500/20 @enderror"
               placeholder="Hasil hitungan">
    </div>
    @error('captcha_input')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
