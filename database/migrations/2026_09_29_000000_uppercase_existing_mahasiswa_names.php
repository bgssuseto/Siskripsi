<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: seragamkan HURUF KAPITAL untuk nama mahasiswa yang sudah ada,
 * menyusul mutator baru Sidang::setNamaMahasiswaAttribute() dan uppercase-on-save
 * untuk akun User berrole mahasiswa — tanpa backfill ini data lama tetap
 * campur-aduk huruf besar/kecil sementara data baru sudah konsisten.
 *
 * Aman dijalankan berkali-kali (idempoten) — UPPER() pada nilai yang sudah
 * kapital semua tidak mengubah apa-apa.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE sidangs SET nama_mahasiswa = UPPER(nama_mahasiswa) WHERE nama_mahasiswa IS NOT NULL');
        DB::statement("UPDATE users SET name = UPPER(name) WHERE role = 'mahasiswa' AND name IS NOT NULL");
    }

    /**
     * Tidak praktis dibalik dengan aman — kapitalisasi asli sebelum migrasi
     * ini tidak tercatat di mana pun untuk dikembalikan. Sengaja no-op.
     */
    public function down(): void
    {
        //
    }
};
