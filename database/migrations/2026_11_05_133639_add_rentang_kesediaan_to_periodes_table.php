<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rentang tanggal yang boleh dipilih dosen saat mengisi kesediaan menguji
 * (diatur admin/koordinator per periode). Kosong = tanpa batas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            if (!Schema::hasColumn('periodes', 'kesediaan_tanggal_mulai')) {
                $table->date('kesediaan_tanggal_mulai')->nullable()->after('lock_form_kesediaan');
            }
            if (!Schema::hasColumn('periodes', 'kesediaan_tanggal_selesai')) {
                $table->date('kesediaan_tanggal_selesai')->nullable()->after('kesediaan_tanggal_mulai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->dropColumn(['kesediaan_tanggal_mulai', 'kesediaan_tanggal_selesai']);
        });
    }
};
