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
            $table->foreignId('ketua_penguji_id')->nullable()->change();
            $table->foreignId('anggota_penguji_1_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreignId('ketua_penguji_id')->nullable(false)->change();
            $table->foreignId('anggota_penguji_1_id')->nullable(false)->change();
        });
    }
};
