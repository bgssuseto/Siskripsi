<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * dosen_pembimbing_utama_id, ketua_penguji_id, and anggota_penguji_1_id were
     * left on ON DELETE CASCADE from the original schema, contradicting the
     * soft-delete design for Dosen (deleting a dosen must never delete the
     * mahasiswa/sidang records referencing them). Align these three with the
     * other two dosen FKs on this table, which already use SET NULL.
     */
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropForeign(['dosen_pembimbing_utama_id']);
            $table->dropForeign(['ketua_penguji_id']);
            $table->dropForeign(['anggota_penguji_1_id']);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreign('dosen_pembimbing_utama_id')->references('id')->on('dosens')->onDelete('set null');
            $table->foreign('ketua_penguji_id')->references('id')->on('dosens')->onDelete('set null');
            $table->foreign('anggota_penguji_1_id')->references('id')->on('dosens')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropForeign(['dosen_pembimbing_utama_id']);
            $table->dropForeign(['ketua_penguji_id']);
            $table->dropForeign(['anggota_penguji_1_id']);
        });

        Schema::table('sidangs', function (Blueprint $table) {
            $table->foreign('dosen_pembimbing_utama_id')->references('id')->on('dosens')->onDelete('cascade');
            $table->foreign('ketua_penguji_id')->references('id')->on('dosens')->onDelete('cascade');
            $table->foreign('anggota_penguji_1_id')->references('id')->on('dosens')->onDelete('cascade');
        });
    }
};
