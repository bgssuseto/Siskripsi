<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kaprodi penandatangan surat (punya masa jabatan). Tiap surat Bebas Prodi
 * menyimpan kaprodi_id saat disetujui, supaya surat lama tetap bertanda tangan
 * kaprodi yang menjabat waktu itu walau kaprodi sudah berganti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kaprodis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->date('mulai_menjabat')->nullable();
            $table->date('selesai_menjabat')->nullable();
            $table->string('ttd_path')->nullable(); // relatif ke disk 'local'
            $table->timestamps();
        });

        Schema::table('bebas_prodis', function (Blueprint $table) {
            $table->foreignId('kaprodi_id')->nullable()->after('tanggal_surat')->constrained('kaprodis')->nullOnDelete();
        });

        // Kaprodi saat ini (sebelumnya hardcode di config) + TTD lama bila sudah diunggah.
        $ttdPath = null;
        $oldTtd = storage_path('app/private/bebas-prodi/ttd_kaprodi.png');
        if (is_file($oldTtd)) {
            $ttdPath = 'bebas-prodi/ttd/kaprodi_awal.png';
            @mkdir(storage_path('app/private/bebas-prodi/ttd'), 0775, true);
            @rename($oldTtd, storage_path('app/private/' . $ttdPath));
        }

        $id = DB::table('kaprodis')->insertGetId([
            'nama' => 'Dr. Ahmad Abdul Chamid, S.Kom., M.Kom',
            'mulai_menjabat' => null, 'selesai_menjabat' => null, 'ttd_path' => $ttdPath,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('bebas_prodis')->where('status', 'disetujui')->update(['kaprodi_id' => $id]);
    }

    public function down(): void
    {
        Schema::table('bebas_prodis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kaprodi_id');
        });
        Schema::dropIfExists('kaprodis');
    }
};
