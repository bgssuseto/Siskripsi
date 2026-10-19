<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            // Waktu mahasiswa PERTAMA KALI mengklik tombol "Join Grup WhatsApp" — dipakai
            // sebagai penanda tahap "Join Grup WA" pada progress pendaftaran mahasiswa.
            // Null = belum pernah mengklik.
            $table->timestamp('wa_joined_at')->nullable()->after('verifikasi_tanggal');
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn('wa_joined_at');
        });
    }
};
