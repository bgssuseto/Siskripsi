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
        Schema::table('periodes', function (Blueprint $table) {
            $table->string('link_grup_wa_skripsi', 500)->nullable()->after('lock_form_kesediaan');
            $table->string('link_grup_wa_sempro', 500)->nullable()->after('link_grup_wa_skripsi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periodes', function (Blueprint $table) {
            $table->dropColumn(['link_grup_wa_skripsi', 'link_grup_wa_sempro']);
        });
    }
};
