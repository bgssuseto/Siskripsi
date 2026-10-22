<?php

/*
 * Halaman bersifat rahasia yang dikunci PIN tambahan (di luar login).
 * PIN disimpan sebagai hash bcrypt — ganti lewat .env:
 *   php -r "echo password_hash('PIN_BARU', PASSWORD_BCRYPT);"
 *   RULE_PENGUJI_PIN_HASH="<hasil di atas>"
 */
return [
    'rule_penguji' => [
        'pin_hash' => env('RULE_PENGUJI_PIN_HASH', '$2y$12$hMRf5OQRyI.jTcJRuaEfFuUgnFF5M6DPhWoSInJ648MPEIWlbIgc.'),

        // Kunci otomatis setelah sekian menit tanpa aktivitas di halaman ini.
        'berlaku_menit' => (int) env('RULE_PENGUJI_PIN_MENIT', 5),

        // Batas salah PIN sebelum dikunci sementara.
        'maks_percobaan' => 5,
        'jeda_detik'     => 300,
    ],
];
