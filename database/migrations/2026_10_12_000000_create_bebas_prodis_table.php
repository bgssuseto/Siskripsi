<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bebas_prodis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Satu pengajuan per mahasiswa — revisi menimpa baris yang sama.
            $table->string('nim', 30)->unique();
            $table->string('nama_mahasiswa');
            $table->string('jalur', 20); // sidang | jurnal (diambil dari pendaftaran skripsi yang lulus)
            $table->string('link_linkedin', 500);
            $table->string('link_program', 500)->nullable();
            $table->string('link_jurnal', 500)->nullable();
            $table->string('file_berkas');
            $table->string('status', 20)->default('menunggu'); // menunggu | disetujui | ditolak
            $table->text('komentar')->nullable();
            $table->unsignedInteger('nomor_surat')->nullable()->unique();
            $table->date('tanggal_surat')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bebas_prodis');
    }
};
