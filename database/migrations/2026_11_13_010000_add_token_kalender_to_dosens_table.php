<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Token rahasia per dosen untuk link langganan kalender (.ics) — dipakai
 * Google Calendar / kalender HP tanpa login. Acak & bisa diganti dosen
 * kapan saja (link lama langsung mati).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->string('token_kalender', 64)->nullable()->unique()->after('no_wa');
        });
    }

    public function down(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->dropUnique(['token_kalender']);
            $table->dropColumn('token_kalender');
        });
    }
};
