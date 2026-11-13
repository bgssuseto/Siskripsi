<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bersihkan data lama: pendaftaran yang berkasnya DITOLAK tapi masih memegang
 * jadwal (tanggal/jam/ruang). Slot tersebut terkunci oleh unique index
 * ruang+tanggal+jam sehingga tidak bisa dipakai mahasiswa lain. Sejak versi ini,
 * menolak berkas otomatis mengosongkan jadwal (Sidang::kosongkanJadwal()).
 * Jadwal yang sudah punya hasil ujian tidak disentuh. Dicatat di log aktivitas.
 */
return new class extends Migration
{
    public function up(): void
    {
        $kolom = ['tanggal', 'jam', 'ruang_id', 'jadwal_terpisah', 'tanggal_pendamping',
            'jam_pendamping', 'ruang_id_pendamping', 'catatan_jadwal_terpisah'];

        $rows = DB::table('sidangs')
            ->where('verifikasi_status', 'ditolak')
            ->where(fn ($q) => $q->whereNull('status_ujian')->orWhere('status_ujian', ''))
            ->where(fn ($q) => $q->whereNotNull('tanggal')->orWhereNotNull('jam')->orWhereNotNull('ruang_id')
                ->orWhereNotNull('tanggal_pendamping')->orWhereNotNull('ruang_id_pendamping'))
            ->get(array_merge(['id', 'nim', 'nama_mahasiswa'], $kolom));

        foreach ($rows as $row) {
            $sebelum = collect((array) $row)->only($kolom)->all();

            DB::table('sidangs')->where('id', $row->id)->update([
                'tanggal' => null, 'jam' => null, 'ruang_id' => null,
                'jadwal_terpisah' => false, 'tanggal_pendamping' => null,
                'jam_pendamping' => null, 'ruang_id_pendamping' => null,
                'catatan_jadwal_terpisah' => null,
            ]);

            if (DB::getSchemaBuilder()->hasTable('activity_logs')) {
                DB::table('activity_logs')->insert([
                    'user_id'       => null,
                    'user_name'     => 'Sistem',
                    'action'        => 'reset_jadwal',
                    'subject_type'  => \App\Models\Sidang::class,
                    'subject_id'    => $row->id,
                    'subject_label' => "{$row->nama_mahasiswa} ({$row->nim})",
                    'description'   => "Mengosongkan jadwal {$row->nama_mahasiswa} ({$row->nim}) karena berkas pendaftarannya ditolak (pembersihan data lama).",
                    'changes'       => json_encode(['before' => $sebelum]),
                    'created_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan otomatis; nilai lama tersimpan di activity_logs.
    }
};
