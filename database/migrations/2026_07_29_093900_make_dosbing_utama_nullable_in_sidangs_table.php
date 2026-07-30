<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make dosen_pembimbing_utama_id nullable so imports can continue
     * even when dosbing utama data is missing or unresolvable.
     */
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreignId('dosen_pembimbing_utama_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreignId('dosen_pembimbing_utama_id')->nullable(false)->change();
        });
    }
};
