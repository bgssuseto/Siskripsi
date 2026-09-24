<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            // Only meaningful when jenis_tugas_akhir/jalur_ta = 'jurnal'. kategori_jurnal
            // is the target/actual publication tier (Sinta 1-4, Q1-4, Seminar
            // Internasional, Lainnya); link_jurnal is filled in once the article is
            // actually published (optional — most students register before that happens).
            $table->string('kategori_jurnal')->nullable()->after('jalur_ta');
            $table->string('link_jurnal', 500)->nullable()->after('kategori_jurnal');
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn(['kategori_jurnal', 'link_jurnal']);
        });
    }
};
