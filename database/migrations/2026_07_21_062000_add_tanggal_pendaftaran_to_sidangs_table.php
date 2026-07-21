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
            if (!Schema::hasColumn('sidangs', 'tanggal_pendaftaran')) {
                $table->date('tanggal_pendaftaran')->nullable()->after('tanggal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            if (Schema::hasColumn('sidangs', 'tanggal_pendaftaran')) {
                $table->dropColumn('tanggal_pendaftaran');
            }
        });
    }
};
