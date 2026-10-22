<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan tanda tangan elektronik (QR) Koordinator Skripsi pada undangan
 * menguji. QR di undangan membuka halaman verifikasi publik berdasarkan `kode`;
 * `rincian` menyimpan potret isi undangan saat ditandatangani.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanda_tangan_undangans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('sidik', 64)->unique(); // sha256 isi undangan — cetak ulang memakai QR yang sama
            $table->foreignId('dosen_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->foreignId('penandatangan_id')->nullable()->constrained('dosens')->nullOnDelete();
            $table->string('nama_penandatangan')->nullable();
            $table->string('nidn_penandatangan')->nullable();
            $table->string('jenis', 20);
            $table->foreignId('periode_id')->nullable()->constrained('periodes')->nullOnDelete();
            $table->string('nama_periode')->nullable();
            $table->string('gelombang')->nullable();
            $table->unsignedInteger('jumlah_mahasiswa')->default(0);
            $table->json('rincian')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanda_tangan_undangans');
    }
};
