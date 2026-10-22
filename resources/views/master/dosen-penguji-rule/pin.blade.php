<x-app-layout title="Rule Komposisi Dosen Penguji">
    <x-slot:header>Rule Komposisi Dosen Penguji</x-slot:header>

    <style>
        @keyframes pin-goyang { 10%, 90% { transform: translateX(-2px); } 20%, 80% { transform: translateX(4px); } 30%, 50%, 70% { transform: translateX(-8px); } 40%, 60% { transform: translateX(8px); } }
        @keyframes pin-muncul { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
        @keyframes pin-denyut { 0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, .35); } 50% { box-shadow: 0 0 0 14px rgba(99, 102, 241, 0); } }
        .pin-kartu { animation: pin-muncul .45s cubic-bezier(.2, .8, .2, 1) both; }
        .pin-goyang { animation: pin-goyang .5s cubic-bezier(.36, .07, .19, .97) both; }
        .pin-ikon { animation: pin-denyut 2.4s ease-in-out infinite; }
        .pin-kotak { transition: border-color .15s, box-shadow .15s, transform .15s, background-color .2s; }
        .pin-kotak.terisi { transform: scale(1.04); }
        @media (prefers-reduced-motion: reduce) { .pin-kartu, .pin-goyang, .pin-ikon { animation: none; } }
    </style>

    <div class="min-h-[calc(100vh-14rem)] flex items-center justify-center py-6"
         x-data="{
            digit: ['', '', '', '', '', ''],
            pesan: '',
            tipe: '',
            proses: false,
            berhasil: false,
            tunggu: 0,
            lihat: false,
            get pin() { return this.digit.join(''); },
            fokus(i) { this.$nextTick(() => this.$refs.kotak?.querySelectorAll('input')[i]?.focus()); },
            isi(i, e) {
                const v = (e.target.value || '').replace(/\D/g, '');
                if (v.length > 1) { this.tempel(v, i); return; }
                this.digit[i] = v;
                if (v && i < 5) this.fokus(i + 1);
                if (this.pin.length === 6) this.kirim();
            },
            mundur(i, e) {
                if (this.digit[i] === '' && i > 0) { e.preventDefault(); this.digit[i - 1] = ''; this.fokus(i - 1); }
            },
            tempel(teks, mulai = 0) {
                const angka = (teks || '').replace(/\D/g, '').slice(0, 6 - mulai).split('');
                angka.forEach((a, k) => this.digit[mulai + k] = a);
                this.fokus(Math.min(mulai + angka.length, 5));
                if (this.pin.length === 6) this.kirim();
            },
            hitungMundur() {
                const t = setInterval(() => { this.tunggu--; if (this.tunggu <= 0) { clearInterval(t); this.tunggu = 0; this.pesan = ''; this.fokus(0); } }, 1000);
            },
            async kirim() {
                if (this.proses || this.tunggu > 0 || this.pin.length !== 6) return;
                this.proses = true; this.pesan = '';
                try {
                    const res = await fetch('{{ route('master.dosen-penguji-rule.buka') }}', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ pin: this.pin }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        this.berhasil = true; this.tipe = 'ok'; this.pesan = data.message;
                        setTimeout(() => window.location.reload(), 650);
                        return;
                    }
                    this.tipe = 'gagal';
                    this.pesan = data.message || 'PIN tidak dapat diperiksa. Coba lagi.';
                    if (res.status === 419) this.pesan = 'Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.';
                    if (data.tunggu) { this.tunggu = data.tunggu; this.hitungMundur(); }
                    this.digit = ['', '', '', '', '', ''];
                    this.$refs.kotak.classList.remove('pin-goyang'); void this.$refs.kotak.offsetWidth; this.$refs.kotak.classList.add('pin-goyang');
                    if (!this.tunggu) this.fokus(0);
                } catch (e) {
                    this.tipe = 'gagal'; this.pesan = 'Gagal terhubung ke server. Periksa koneksi Anda.';
                } finally {
                    this.proses = false;
                }
            },
         }"
         x-init="fokus(0)">

        <div class="pin-kartu w-full max-w-md">
            <div class="relative overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl shadow-slate-900/5">
                <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-indigo-50 to-transparent dark:from-indigo-500/10"></span>

                <div class="relative px-6 sm:px-8 pt-8 pb-7 text-center">
                    <div class="mx-auto w-16 h-16 rounded-2xl flex items-center justify-center text-white shadow-lg transition-all duration-500"
                         :class="berhasil ? 'bg-gradient-to-br from-emerald-500 to-teal-600 shadow-emerald-500/30 scale-110' : 'pin-ikon bg-gradient-to-br from-indigo-500 to-violet-600 shadow-indigo-500/30'">
                        <svg x-show="!berhasil" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <svg x-show="berhasil" x-cloak class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 117.9-1M5 21h14a2 2 0 002-2v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    </div>

                    <h1 class="mt-5 text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100">Halaman Rahasia</h1>
                    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        <strong class="text-slate-700 dark:text-slate-200">Rule Komposisi Dosen Penguji</strong> dilindungi PIN.<br class="hidden sm:block">
                        Masukkan 6 digit PIN untuk melanjutkan.
                    </p>

                    <form @submit.prevent="kirim()" class="mt-7">
                        <div x-ref="kotak" class="flex justify-center gap-2 sm:gap-2.5" @paste.prevent="tempel($event.clipboardData.getData('text'))">
                            <template x-for="(d, i) in digit" :key="i">
                                <input :type="lihat ? 'text' : 'password'" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                                       :aria-label="'Digit PIN ke-' + (i + 1)"
                                       :value="digit[i]"
                                       @input="isi(i, $event)"
                                       @keydown.backspace="mundur(i, $event)"
                                       @keydown.left.prevent="fokus(Math.max(i - 1, 0))"
                                       @keydown.right.prevent="fokus(Math.min(i + 1, 5))"
                                       @focus="$el.select()"
                                       :disabled="proses || berhasil || tunggu > 0"
                                       class="pin-kotak w-11 h-14 sm:w-12 sm:h-14 text-center text-xl font-extrabold rounded-2xl border-2 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 outline-none focus:ring-4 disabled:opacity-60"
                                       :class="[
                                           digit[i] ? 'terisi' : '',
                                           berhasil ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-500/10 focus:ring-emerald-500/20'
                                                    : (tipe === 'gagal' && pesan ? 'border-rose-300 dark:border-rose-500/50 focus:border-rose-500 focus:ring-rose-500/15'
                                                    : 'border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-indigo-500/15')
                                       ]">
                            </template>
                        </div>

                        <div class="mt-4 min-h-[1.5rem]">
                            <p x-show="pesan" x-cloak class="text-xs font-semibold" :class="tipe === 'ok' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                <span x-text="pesan"></span>
                                <template x-if="tunggu > 0"><span x-text="' (' + Math.floor(tunggu / 60) + ':' + String(tunggu % 60).padStart(2, '0') + ')'"></span></template>
                            </p>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <button type="button" @click="lihat = !lihat" class="text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 inline-flex items-center gap-1.5">
                                <x-icon name="eye" class="w-4 h-4" />
                                <span x-text="lihat ? 'Sembunyikan' : 'Tampilkan'"></span>
                            </button>
                            <button type="submit" class="ui-btn-primary min-w-[8.5rem] justify-center" :disabled="pin.length !== 6 || proses || berhasil || tunggu > 0">
                                <svg x-show="proses" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                                <span x-text="berhasil ? 'Terbuka' : (proses ? 'Memeriksa…' : 'Buka Halaman')"></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="relative px-6 sm:px-8 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/40 text-[11px] text-slate-500 dark:text-slate-400 flex items-start gap-2 text-left">
                    <x-icon name="shield" class="w-4 h-4 shrink-0 mt-px text-indigo-500" />
                    <span>Halaman terkunci otomatis setelah {{ config('rahasia.rule_penguji.berlaku_menit') }} menit tanpa aktivitas. Setiap percobaan PIN tercatat di Riwayat Aktivitas.</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
