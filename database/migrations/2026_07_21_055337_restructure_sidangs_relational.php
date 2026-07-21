<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            // Drop old text columns
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
                'waktu'
            ]);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            // Add relational foreign key columns
            $table->unsignedSmallInteger('no_urut')->nullable()->after('id');
            
            $table->foreignId('dosen_pembimbing_utama_id')->after('judul_skripsi')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('dosen_pembimbing_pendamping_id')->after('dosen_pembimbing_utama_id')->nullable()->constrained('dosens')->onDelete('set null');
            
            $table->foreignId('ketua_penguji_id')->after('dosen_pembimbing_pendamping_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_1_id')->after('ketua_penguji_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_2_id')->after('anggota_penguji_1_id')->nullable()->constrained('dosens')->onDelete('set null');
            
            $table->foreignId('ruang_id')->after('anggota_penguji_2_id')->nullable()->constrained('ruangs')->onDelete('set null');
            $table->foreignId('periode_id')->after('ruang_id')->nullable()->constrained('periodes')->onDelete('set null');
            
            $table->date('tanggal')->nullable()->after('periode_id');
            $table->string('jam')->nullable()->after('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            // Drop foreign keys and columns
            $table->dropForeign(['dosen_pembimbing_utama_id']);
            $table->dropForeign(['dosen_pembimbing_pendamping_id']);
            $table->dropForeign(['ketua_penguji_id']);
            $table->dropForeign(['anggota_penguji_1_id']);
            $table->dropForeign(['anggota_penguji_2_id']);
            $table->dropForeign(['ruang_id']);
            $table->dropForeign(['periode_id']);

            $table->dropColumn([
                'no_urut',
                'dosen_pembimbing_utama_id',
                'dosen_pembimbing_pendamping_id',
                'ketua_penguji_id',
                'anggota_penguji_1_id',
                'anggota_penguji_2_id',
                'ruang_id',
                'periode_id',
                'tanggal',
                'jam'
            ]);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            // Add back the temporary text columns
            $table->unsignedSmallInteger('no_urut')->nullable();
            $table->string('dosbing_utama')->nullable();
            $table->string('dosbing_pendamping')->nullable();
            $table->string('ketua_penguji')->nullable();
            $table->string('penguji_1')->nullable();
            $table->string('penguji_2')->nullable();
            $table->string('hari')->nullable();
            $table->string('jam')->nullable();
            $table->string('ruangan')->nullable();
            $table->string('waktu')->nullable();
        });
    }
};
