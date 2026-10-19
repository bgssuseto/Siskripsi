<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kode acak untuk QR tanda tangan elektronik — dipindai untuk membuka halaman
 * verifikasi publik yang menyatakan surat asli & sah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bebas_prodis', function (Blueprint $table) {
            $table->string('kode_verifikasi', 40)->nullable()->unique()->after('nomor_surat');
        });

        DB::table('bebas_prodis')->whereNotNull('nomor_surat')->whereNull('kode_verifikasi')->pluck('id')
            ->each(fn ($id) => DB::table('bebas_prodis')->where('id', $id)->update(['kode_verifikasi' => Str::random(32)]));
    }

    public function down(): void
    {
        Schema::table('bebas_prodis', function (Blueprint $table) {
            $table->dropUnique(['kode_verifikasi']);
            $table->dropColumn('kode_verifikasi');
        });
    }
};
