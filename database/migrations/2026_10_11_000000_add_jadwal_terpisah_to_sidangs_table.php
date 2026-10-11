<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dukungan "jadwal terpisah" khusus Sempro: ketika Pembimbing Utama dan
 * Pembimbing Pendamping tidak bisa menyamakan jadwal, mahasiswa diuji di
 * DUA sesi terpisah (tanggal/jam/ruang masing-masing) alih-alih satu sesi
 * bersama. Kolom `tanggal`/`jam`/`ruang_id` yang sudah ada tetap dipakai
 * untuk sesi Pembimbing Utama (Ketua Penguji); kolom baru di sini khusus
 * menampung sesi kedua milik Pembimbing Pendamping (Anggota Penguji 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->boolean('jadwal_terpisah')->default(false)->after('jam');
            $table->date('tanggal_pendamping')->nullable()->after('jadwal_terpisah');
            $table->string('jam_pendamping', 100)->nullable()->after('tanggal_pendamping');
            $table->foreignId('ruang_id_pendamping')->nullable()->after('jam_pendamping')
                ->constrained('ruangs')->onDelete('set null');
            $table->text('catatan_jadwal_terpisah')->nullable()->after('ruang_id_pendamping');

            // Cermin dari sidangs_ruang_tanggal_jam_unique (lihat
            // 2026_09_23_000000_add_unique_constraints...) untuk sesi
            // Pembimbing Pendamping — menutup celah race-condition yang sama
            // (dua admin memplot ke slot ruang/tanggal/jam yang sama persis
            // secara bersamaan) untuk sesi kedua ini. NULL diperlakukan unik
            // oleh MySQL/InnoDB, jadi baris yang sesi pendampingnya belum
            // diisi (jadwal_terpisah=false) otomatis tidak terpengaruh.
            $table->unique(['ruang_id_pendamping', 'tanggal_pendamping', 'jam_pendamping'], 'sidangs_ruang_tanggal_jam_pendamping_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropUnique('sidangs_ruang_tanggal_jam_pendamping_unique');
            $table->dropConstrainedForeignId('ruang_id_pendamping');
            $table->dropColumn(['jadwal_terpisah', 'tanggal_pendamping', 'jam_pendamping', 'catatan_jadwal_terpisah']);
        });
    }
};
