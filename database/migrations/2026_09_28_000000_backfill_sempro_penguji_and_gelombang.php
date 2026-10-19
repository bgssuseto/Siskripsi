<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill data untuk 2 aturan baru (lihat App\Models\Sidang):
 *
 * 1) Dewan penguji Sempro SELALU = pembimbing mahasiswa sendiri. Sebelum ini,
 *    SemproController selalu men-set ketua_penguji_id/anggota_penguji_1_id/2_id
 *    ke NULL, jadi tanpa backfill: Undangan Menguji & Asisten Plotting tidak
 *    mengenali pembimbing Sempro sebagai penguji untuk data yang sudah ada.
 *
 * 2) Kolom `gelombang` pada Sidang cuma dihitung otomatis saat Sidang itu
 *    sendiri dibuat/diupdate — Sidang yang didaftarkan SEBELUM gelombangnya
 *    (Master Gelombang) dibuat/diedit tetap NULL selamanya tanpa backfill ini,
 *    sehingga tidak terdeteksi oleh filter Gelombang di Asisten Plotting/Undangan.
 *
 * Aman dijalankan berkali-kali (idempoten).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            UPDATE sidangs
            SET ketua_penguji_id = dosen_pembimbing_utama_id,
                anggota_penguji_1_id = dosen_pembimbing_pendamping_id,
                anggota_penguji_2_id = NULL
            WHERE jenis_tugas_akhir = 'sempro'
        ");

        $skripsiBucket = ['skripsi', 'sidang', 'jurnal'];
        $waves = DB::table('pendaftaran_periodes')->orderBy('gelombang')->get();

        foreach ($waves as $wave) {
            $jenisList = $wave->jenis === 'sempro' ? ['sempro'] : $skripsiBucket;

            DB::table('sidangs')
                ->where('periode_id', $wave->periode_id)
                ->whereIn('jenis_tugas_akhir', $jenisList)
                ->whereNotNull('tanggal_pendaftaran')
                ->whereDate('tanggal_pendaftaran', '>=', $wave->tanggal_mulai)
                ->whereDate('tanggal_pendaftaran', '<=', $wave->tanggal_selesai)
                ->update(['gelombang' => $wave->gelombang]);
        }
    }

    /**
     * Tidak praktis dibalik dengan aman — bisa menimpa penguji Sempro yang
     * (di luar dugaan) sempat di-assign manual sebelum migrasi ini, dan tidak
     * ada catatan nilai `gelombang` sebelumnya untuk dikembalikan. Sengaja no-op.
     */
    public function down(): void
    {
        //
    }
};
