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
        Schema::create('sidangs', function (Blueprint $table) {
            $table->id();
            $table->string('nim');
            $table->string('nama_mahasiswa');
            $table->text('judul_skripsi');

            // Foreign keys to dosens
            $table->foreignId('dosen_pembimbing_utama_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('dosen_pembimbing_pendamping_id')->nullable()->constrained('dosens')->onDelete('set null');
            $table->foreignId('ketua_penguji_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_1_id')->constrained('dosens')->onDelete('cascade');
            $table->foreignId('anggota_penguji_2_id')->nullable()->constrained('dosens')->onDelete('set null');

            // Foreign key to ruangs
            $table->foreignId('ruang_id')->constrained('ruangs')->onDelete('cascade');

            // Schedule info
            $table->date('tanggal');
            $table->string('waktu'); // e.g. "08:00 - 10:00"
            $table->enum('jenis_tugas_akhir', ['sidang', 'jurnal'])->default('sidang');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sidangs');
    }
};
