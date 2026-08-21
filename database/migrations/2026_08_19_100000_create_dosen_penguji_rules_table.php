<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dosen_penguji_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dosen_id')->unique()->constrained('dosens')->cascadeOnDelete();
            $table->json('boleh_dosen_ids')->nullable();
            $table->json('tidak_boleh_dosen_ids')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dosen_penguji_rules');
    }
};
