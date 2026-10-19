@php
    // Kelas ditulis literal supaya terdeteksi Tailwind.
    $items = [
        ['clock', 'Kehadiran', 'bg-amber-50 text-amber-600 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/20',
            'Datang ke ruangan ujian pada <strong>pukul 07.00 WIB</strong> untuk mengecek kesiapan ruangan, proyektor, dan peralatan ujian lainnya sebelum sidang dimulai agar pelaksanaan berjalan lancar.'],
        ['user', 'Pakaian & Atribut', 'bg-indigo-50 text-indigo-600 ring-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-300 dark:ring-indigo-500/20',
            'Berpakaian rapi: jas almamater UMK, dalaman baju putih berkerah, dasi hitam, dan sepatu pantofel hitam.'],
        ['document', 'Manuskrip Skripsi', 'bg-emerald-50 text-emerald-600 ring-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/20',
            'Membawa cetak manuskrip skripsi sebanyak <strong>4 eksemplar</strong>.'],
        ['refresh', 'Waktu Pelaksanaan', 'bg-sky-50 text-sky-600 ring-sky-100 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-500/20',
            'Jadwal sidang <strong>dapat maju atau mundur</strong> dari jam yang ditetapkan, tergantung kondisi masing-masing penguji.'],
    ];
@endphp
<section class="bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-700/80 rounded-3xl shadow-sm p-5 sm:p-6 my-6">
    <div class="flex items-start gap-3 pb-4 mb-4 border-b border-slate-100 dark:border-slate-800">
        <span class="w-10 h-10 shrink-0 rounded-2xl ring-1 ring-inset flex items-center justify-center bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
            <x-icon name="info" class="w-5 h-5" />
        </span>
        <div>
            <h3 class="text-[15px] font-extrabold tracking-tight text-slate-900 dark:text-slate-100">Informasi & Tata Tertib Persiapan Sidang</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Harap diperhatikan dan dipersiapkan oleh seluruh mahasiswa sebelum pelaksanaan sidang.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($items as [$ikon, $judul, $warna, $isi])
            <div class="rounded-2xl bg-slate-50 dark:bg-slate-800/50 p-4 flex items-start gap-3">
                <span class="w-9 h-9 shrink-0 rounded-xl ring-1 ring-inset flex items-center justify-center {{ $warna }}">
                    <x-icon :name="$ikon" class="w-[18px] h-[18px]" />
                </span>
                <div class="min-w-0">
                    <h4 class="text-xs font-extrabold text-slate-900 dark:text-slate-100">{{ $judul }}</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 leading-relaxed [&_strong]:text-slate-900 dark:[&_strong]:text-slate-100">{!! $isi !!}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
