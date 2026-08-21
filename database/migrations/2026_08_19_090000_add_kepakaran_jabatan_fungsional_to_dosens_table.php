<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->string('kepakaran')->nullable()->after('nama_dosen');
            $table->string('jabatan_fungsional')->nullable()->after('kepakaran');
        });
    }

    public function down(): void
    {
        Schema::table('dosens', function (Blueprint $table) {
            $table->dropColumn(['kepakaran', 'jabatan_fungsional']);
        });
    }
};
