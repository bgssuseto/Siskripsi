<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->string('jalur_ta')->nullable()->after('jenis_tugas_akhir');
        });

        // Backfill: untuk record skripsi/jurnal yang sudah ada, jalur sudah
        // eksplisit dari jenis_tugas_akhir. Untuk sempro lama, jalurnya tidak
        // pernah tersimpan (bug lama) sehingga dibiarkan null / "Belum Ditentukan".
        DB::table('sidangs')->where('jenis_tugas_akhir', 'jurnal')->update(['jalur_ta' => 'jurnal']);
        DB::table('sidangs')->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang'])->update(['jalur_ta' => 'sidang']);
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn('jalur_ta');
        });
    }
};
