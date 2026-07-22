<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sidangs MODIFY COLUMN jenis_tugas_akhir VARCHAR(50) NOT NULL DEFAULT 'skripsi'");
        DB::table('sidangs')->where('jenis_tugas_akhir', 'sidang')->update(['jenis_tugas_akhir' => 'skripsi']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sidangs')->where('jenis_tugas_akhir', 'skripsi')->update(['jenis_tugas_akhir' => 'sidang']);
    }
};
