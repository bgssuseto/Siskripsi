<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bebas_prodis', function (Blueprint $table) {
            // Poster dikumpulkan sebagai link Google Drive, bukan di dalam berkas PDF.
            $table->string('link_poster', 500)->nullable()->after('jalur');
        });
    }

    public function down(): void
    {
        Schema::table('bebas_prodis', function (Blueprint $table) {
            $table->dropColumn('link_poster');
        });
    }
};
