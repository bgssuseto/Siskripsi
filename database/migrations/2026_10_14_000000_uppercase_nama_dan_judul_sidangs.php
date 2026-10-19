<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seragamkan nama mahasiswa & judul skripsi/proposal yang SUDAH ada menjadi
 * HURUF KAPITAL. Data baru sudah otomatis kapital lewat mutator di model
 * Sidang (setNamaMahasiswaAttribute / setJudulSkripsiAttribute).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('sidangs')->update([
            'nama_mahasiswa' => DB::raw('UPPER(TRIM(nama_mahasiswa))'),
            'judul_skripsi'  => DB::raw('UPPER(TRIM(judul_skripsi))'),
        ]);
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan: huruf besar/kecil aslinya tidak disimpan.
    }
};
