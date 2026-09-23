<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Closes two race-condition windows that only a DB-level constraint can fully
 * close (two near-simultaneous requests can both pass an application-level
 * "does this already exist?" check before either one commits its write):
 *
 *  1. A student double-clicking / retrying "Daftar" could end up with two
 *     Sidang rows for the same (nim, jenis_tugas_akhir, periode_id).
 *  2. Two admins plotting overlapping proposals at the same moment could both
 *     pass their own conflict check and double-book the same (ruang_id,
 *     tanggal, jam) slot.
 *
 * MySQL/InnoDB treats each NULL as distinct in a unique index, so rows that
 * are not yet scheduled (ruang_id/tanggal/jam still null) or have no
 * periode_id are naturally exempt from these constraints — only genuinely
 * identical, fully-populated combinations are rejected.
 *
 * Before adding either constraint, this checks for pre-existing duplicates
 * and aborts with a clear, actionable list instead of leaving Laravel's raw
 * "Duplicate entry" MySQL error to surface (or worse, silently deleting data
 * to force the migration through) — resolving those is a judgment call for
 * whoever runs this migration, not something to automate away.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->assertNoDuplicates(
            "SELECT nim, jenis_tugas_akhir, periode_id, COUNT(*) AS jumlah
             FROM sidangs
             WHERE periode_id IS NOT NULL
             GROUP BY nim, jenis_tugas_akhir, periode_id
             HAVING COUNT(*) > 1",
            'sidangs (nim, jenis_tugas_akhir, periode_id)',
            fn ($row) => "NIM {$row->nim} / {$row->jenis_tugas_akhir} / periode_id {$row->periode_id} ({$row->jumlah}x)"
        );

        $this->assertNoDuplicates(
            "SELECT ruang_id, tanggal, jam, COUNT(*) AS jumlah
             FROM sidangs
             WHERE ruang_id IS NOT NULL AND tanggal IS NOT NULL AND jam IS NOT NULL
             GROUP BY ruang_id, tanggal, jam
             HAVING COUNT(*) > 1",
            'sidangs (ruang_id, tanggal, jam)',
            fn ($row) => "ruang_id {$row->ruang_id} / {$row->tanggal} / jam {$row->jam} ({$row->jumlah}x)"
        );

        Schema::table('sidangs', function (Blueprint $table) {
            $table->unique(['nim', 'jenis_tugas_akhir', 'periode_id'], 'sidangs_nim_jenis_periode_unique');
            $table->unique(['ruang_id', 'tanggal', 'jam'], 'sidangs_ruang_tanggal_jam_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sidangs', function (Blueprint $table) {
            $table->dropUnique('sidangs_nim_jenis_periode_unique');
            $table->dropUnique('sidangs_ruang_tanggal_jam_unique');
        });
    }

    private function assertNoDuplicates(string $sql, string $label, \Closure $describe): void
    {
        $duplicates = DB::select($sql);

        if (empty($duplicates)) {
            return;
        }

        $details = implode("\n  - ", array_map($describe, $duplicates));

        throw new \RuntimeException(
            "Tidak bisa menambahkan unique constraint pada {$label}: sudah ada data duplikat di tabel sidangs. " .
            "Selesaikan/gabungkan data berikut secara manual dulu (mis. lewat Master Skripsi/Sempro), " .
            "baru jalankan migration ini lagi:\n  - {$details}"
        );
    }
};
