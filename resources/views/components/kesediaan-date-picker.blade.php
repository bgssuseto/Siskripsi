@props([
    // Tanggal (Y-m-d) yang SUDAH tersimpan sebelumnya — cuma ditandai hijau di
    // kalender supaya dosen tahu mana yang sudah diisi. Tetap bisa dipilih lagi
    // karena tanggal yang sama boleh diisi untuk gelombang lain.
    'existing' => [],
    // Tanggal (Y-m-d) yang langsung terpilih saat form dibuka (mis. old input
    // setelah validasi gagal).
    'selected' => [],
    'keterangan' => '',
])

{{--
    Kalender multi-pilih untuk form kesediaan menguji. Dosen cukup klik
    beberapa tanggal sekaligus (klik lagi untuk batal), lalu satu catatan
    dipakai untuk semua tanggal terpilih. Hasilnya dikirim sebagai
    slots[i][tanggal] + slots[i][keterangan] — format yang sama dengan
    form lama, jadi controller tidak perlu diubah.
--}}
<div x-data="{
        today: '{{ now()->format('Y-m-d') }}',
        existing: @js(array_values($existing)),
        selected: @js(array_values($selected)),
        keterangan: @js($keterangan),
        year: {{ now()->year }},
        month: {{ now()->month - 1 }},
        dayNames: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
        pad(n) { return String(n).padStart(2, '0'); },
        ymd(d) { return this.year + '-' + this.pad(this.month + 1) + '-' + this.pad(d); },
        get cells() {
            // Senin sebagai awal minggu.
            const offset = (new Date(this.year, this.month, 1).getDay() + 6) % 7;
            const total = new Date(this.year, this.month + 1, 0).getDate();
            const cells = Array(offset).fill(null);
            for (let d = 1; d <= total; d++) cells.push({ day: d, date: this.ymd(d), col: (offset + d - 1) % 7 });
            return cells;
        },
        get sorted() { return [...this.selected].sort(); },
        isPast(date) { return date < this.today; },
        isExisting(date) { return this.existing.includes(date); },
        isSelectable(date) { return !this.isPast(date); },
        isSelected(date) { return this.selected.includes(date); },
        toggle(date) {
            if (!this.isSelectable(date)) return;
            this.isSelected(date)
                ? this.selected = this.selected.filter(d => d !== date)
                : this.selected.push(date);
        },
        // Pilih/batalkan sekaligus sekelompok tanggal: kalau semuanya sudah
        // terpilih → batalkan semua, kalau belum → pilih semua.
        toggleMany(dates) {
            dates = dates.filter(d => this.isSelectable(d));
            if (!dates.length) return;
            const allOn = dates.every(d => this.isSelected(d));
            this.selected = allOn
                ? this.selected.filter(d => !dates.includes(d))
                : [...new Set([...this.selected, ...dates])];
        },
        toggleColumn(col) { this.toggleMany(this.cells.filter(c => c && c.col === col).map(c => c.date)); },
        toggleWorkdays() { this.toggleMany(this.cells.filter(c => c && c.col < 5).map(c => c.date)); },
        prevMonth() { this.month === 0 ? (this.month = 11, this.year--) : this.month--; },
        nextMonth() { this.month === 11 ? (this.month = 0, this.year++) : this.month++; },
        get canGoPrev() { return this.year + '-' + this.pad(this.month + 1) > this.today.slice(0, 7); },
        label(date) {
            const [y, m, d] = date.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });
        },
     }" {{ $attributes->merge(['class' => 'space-y-3']) }}>

    <div class="flex items-center justify-between">
        <label class="text-xs font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Pilih Tanggal Ketersediaan *</label>
        <span class="text-[11px] font-bold px-2.5 py-1 rounded-lg"
              :class="selected.length ? 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400'"
              x-text="selected.length + ' tanggal dipilih'"></span>
    </div>
    <p class="text-[11px] text-slate-500 dark:text-slate-400 -mt-1">Klik tanggal untuk memilih (klik lagi untuk batal). Klik nama hari untuk memilih semua hari itu di bulan ini.</p>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/60 p-3 sm:p-4 select-none">
        {{-- Navigasi bulan --}}
        <div class="flex items-center justify-between mb-3">
            <button type="button" @click="prevMonth()" :disabled="!canGoPrev"
                    class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer" aria-label="Bulan sebelumnya">‹</button>
            <div class="text-sm font-extrabold text-slate-800 dark:text-slate-100" x-text="monthNames[month] + ' ' + year"></div>
            <button type="button" @click="nextMonth()"
                    class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer" aria-label="Bulan berikutnya">›</button>
        </div>

        {{-- Header hari (klik = pilih semua hari itu di bulan ini) --}}
        <div class="grid grid-cols-7 gap-1 mb-1">
            <template x-for="(name, col) in dayNames" :key="col">
                <button type="button" @click="toggleColumn(col)"
                        class="text-[10px] sm:text-[11px] font-extrabold uppercase py-1 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/40 cursor-pointer"
                        :class="col >= 5 ? 'text-rose-500 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'"
                        :title="'Pilih semua hari ' + name + ' di bulan ini'"
                        x-text="name"></button>
            </template>
        </div>

        {{-- Grid tanggal --}}
        <div class="grid grid-cols-7 gap-1">
            <template x-for="(cell, i) in cells" :key="i">
                <div>
                    <template x-if="cell">
                        <button type="button" @click="toggle(cell.date)" :disabled="!isSelectable(cell.date)"
                                class="relative w-full aspect-square sm:aspect-auto sm:h-10 rounded-xl text-xs sm:text-sm font-bold transition-colors"
                                :class="{
                                    'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 hover:bg-indigo-700 cursor-pointer': isSelected(cell.date),
                                    'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 hover:border-indigo-400 cursor-pointer': isExisting(cell.date) && isSelectable(cell.date) && !isSelected(cell.date),
                                    'text-slate-300 dark:text-slate-600 cursor-not-allowed': isPast(cell.date),
                                    'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 cursor-pointer': isSelectable(cell.date) && !isSelected(cell.date) && !isExisting(cell.date),
                                    'ring-2 ring-amber-400': cell.date === today,
                                }"
                                :title="isPast(cell.date) ? 'Tanggal sudah lewat' : (isExisting(cell.date) ? 'Sudah pernah diisi' : '')"
                                x-text="cell.day"></button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Aksi cepat & legenda --}}
        <div class="flex flex-wrap items-center justify-between gap-2 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
            <div class="flex flex-wrap gap-2">
                <button type="button" @click="toggleWorkdays()"
                        class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700 hover:bg-indigo-100 cursor-pointer">Semua Sen–Jum bulan ini</button>
                <button type="button" @click="selected = []" x-show="selected.length"
                        class="text-[11px] font-bold px-2.5 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 cursor-pointer">Kosongkan pilihan</button>
            </div>
            <div class="flex items-center gap-3 text-[10px] font-semibold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-indigo-600"></span>Dipilih</span>
                <span class="flex items-center gap-1" x-show="existing.length"><span class="w-3 h-3 rounded bg-emerald-200 dark:bg-emerald-800"></span>Sudah diisi</span>
            </div>
        </div>
    </div>

    {{-- Ringkasan tanggal terpilih (bisa juga dihapus dari sini) --}}
    <div x-show="selected.length" class="flex flex-wrap gap-1.5">
        <template x-for="date in sorted" :key="date">
            <span class="inline-flex items-center gap-1 text-[11px] font-bold pl-2.5 pr-1 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                <span x-text="label(date)"></span>
                <button type="button" @click="toggle(date)" class="w-4 h-4 rounded flex items-center justify-center hover:bg-indigo-200 dark:hover:bg-indigo-800 cursor-pointer" aria-label="Hapus tanggal">✕</button>
            </span>
        </template>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">Catatan untuk semua tanggal di atas (Opsional)</label>
        <input type="text" x-model="keterangan" placeholder="Contoh: bisa luring/online, hanya pagi"
               class="w-full text-sm p-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-indigo-500">
    </div>

    {{-- Field yang benar-benar dikirim ke server --}}
    <template x-for="(date, i) in sorted" :key="date">
        <div>
            <input type="hidden" :name="'slots[' + i + '][tanggal]'" :value="date">
            <input type="hidden" :name="'slots[' + i + '][keterangan]'" :value="keterangan">
        </div>
    </template>

    {{-- Cegah submit tanpa tanggal: input tersembunyi ini invalid selama belum ada yang dipilih --}}
    <input type="text" tabindex="-1" aria-hidden="true" class="sr-only"
           x-effect="$el.setCustomValidity(selected.length ? '' : 'Pilih minimal satu tanggal ketersediaan.')">
</div>
