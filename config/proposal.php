<?php

return [
    // Nomor Surat Keterangan Pengumpulan Proposal pertama yang diterbitkan sistem
    // (format {nomor}/PROPOSAL.TI.UMK/{bulan}/{tahun}). Nomor berikutnya = nomor
    // terbesar yang sudah terbit + 1. Nomor tetap bisa diubah manual saat menerima.
    'nomor_awal' => (int) env('PROPOSAL_NOMOR_AWAL', 1285),
];
