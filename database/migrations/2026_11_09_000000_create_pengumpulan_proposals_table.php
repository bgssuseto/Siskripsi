<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengumpulan Proposal Skripsi ke Koordinator (satu baris per pendaftaran
 * Sempro). Bila diterima, mahasiswa bisa mencetak Surat Keterangan Pengumpulan
 * Proposal bertanda tangan QR; bila ditolak, catatannya tampil ke mahasiswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengumpulan_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidang_id')->unique()->constrained('sidangs')->cascadeOnDelete();
            $table->string('status', 20)->default('belum'); // belum | diterima | ditolak
            $table->text('catatan')->nullable();
            $table->unsignedInteger('nomor_surat')->nullable()->unique();
            $table->string('kode_verifikasi', 40)->nullable()->unique();
            $table->date('tanggal_surat')->nullable();
            $table->foreignId('penandatangan_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->string('nama_penandatangan')->nullable();
            $table->string('nidn_penandatangan')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengumpulan_proposals');
    }
};
