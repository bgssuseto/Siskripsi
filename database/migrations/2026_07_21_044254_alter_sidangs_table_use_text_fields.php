<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restrukturisasi tabel sidangs:
 * - Drop foreign key columns (ke dosens & ruangs)
 * - Ganti dengan kolom string bebas (sesuai format import Excel)
 * - Tambah kolom 'no_urut', 'hari' (string)
 * - Jadikan 'tanggal' nullable string
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            // Drop foreign key columns
            $table->dropForeign(['dosen_pembimbing_utama_id']);
            $table->dropForeign(['dosen_pembimbing_pendamping_id']);
            $table->dropForeign(['ketua_penguji_id']);
            $table->dropForeign(['anggota_penguji_1_id']);
            $table->dropForeign(['anggota_penguji_2_id']);
            $table->dropForeign(['ruang_id']);

            $table->dropColumn([
                'dosen_pembimbing_utama_id',
                'dosen_pembimbing_pendamping_id',
                'ketua_penguji_id',
                'anggota_penguji_1_id',
                'anggota_penguji_2_id',
                'ruang_id',
                'tanggal',
            ]);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            // Tambah kolom string bebas
            $table->unsignedSmallInteger('no_urut')->nullable()->after('id');
            $table->string('dosbing_utama')->nullable()->after('judul_skripsi');
            $table->string('dosbing_pendamping')->nullable()->after('dosbing_utama');
            $table->string('ketua_penguji')->nullable()->after('dosbing_pendamping');
            $table->string('penguji_1')->nullable()->after('ketua_penguji');
            $table->string('penguji_2')->nullable()->after('penguji_1');
            $table->string('hari')->nullable()->after('penguji_2');      // e.g. "Rabu, 15 Juli 2026"
            $table->string('jam')->nullable()->after('hari');            // e.g. "08.00 - 09.00"
            $table->string('ruangan')->nullable()->after('jam');         // e.g. "J.5.05"
            $table->string('waktu')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn([
                'no_urut',
                'dosbing_utama',
                'dosbing_pendamping',
                'ketua_penguji',
                'penguji_1',
                'penguji_2',
                'hari',
                'jam',
                'ruangan',
            ]);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreignId('dosen_pembimbing_utama_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('dosen_pembimbing_pendamping_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->foreignId('ketua_penguji_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_1_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_2_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->foreignId('ruang_id')->constrained('ruangs')->onDelete('cascade');
            $table->date('tanggal');
        });
    }
};
