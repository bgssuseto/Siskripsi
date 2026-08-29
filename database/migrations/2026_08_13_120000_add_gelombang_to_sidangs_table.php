<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->integer('gelombang')->nullable()->after('periode_id');
        });

        // Backfill existing rows by matching tanggal_pendaftaran against
        // the configured PendaftaranPeriode (Master Gelombang) date ranges.
        $gelombangRows = DB::table('pendaftaran_periodes')->get();

        foreach ($gelombangRows as $pp) {
            $query = DB::table('sidangs')
                ->where('periode_id', $pp->periode_id)
                ->whereNotNull('tanggal_pendaftaran')
                ->whereBetween('tanggal_pendaftaran', [$pp->tanggal_mulai, $pp->tanggal_selesai]);

            if ($pp->jenis === 'sempro') {
                $query->where('jenis_tugas_akhir', 'sempro');
            } else {
                $query->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal']);
            }

            $query->update(['gelombang' => $pp->gelombang]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropColumn('gelombang');
        });
    }
};
