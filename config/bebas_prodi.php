<?php

return [
    // Nomor surat pertama yang diterbitkan sistem (format TI/BP/{nomor}).
    // Nomor berikutnya = nomor terbesar yang sudah terbit + 1.
    'nomor_awal' => (int) env('BEBAS_PRODI_NOMOR_AWAL', 1268),

    // NIM yang boleh mengakses Bebas Prodi walau belum lulus Sempro & Skripsi
    // (untuk testing). Pisahkan dengan koma di .env, kosongkan untuk mematikan.
    'bypass_nims' => array_filter(array_map('trim', explode(',', env('BEBAS_PRODI_BYPASS_NIMS', '202251115')))),

    'kaprodi_nama' => env('BEBAS_PRODI_KAPRODI_NAMA', 'Dr. Ahmad Abdul Chamid, S.Kom., M.Kom'),

    // Gambar tanda tangan Kaprodi (PNG transparan) yang ditempel di surat.
    // Diunggah super admin lewat halaman Bebas Prodi; disimpan di luar public/.
    'ttd_path' => storage_path('app/private/bebas-prodi/ttd_kaprodi.png'),
];
