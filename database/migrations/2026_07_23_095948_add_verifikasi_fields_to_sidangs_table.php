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
            $table->string('verifikasi_status')->default('menunggu')->after('jenis_tugas_akhir'); // 'menunggu', 'disetujui', 'ditolak'
            $table->text('verifikasi_komentar')->nullable()->after('verifikasi_status');
            $table->timestamp('verifikasi_tanggal')->nullable()->after('verifikasi_komentar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn(['verifikasi_status', 'verifikasi_komentar', 'verifikasi_tanggal']);
        });
    }
};
