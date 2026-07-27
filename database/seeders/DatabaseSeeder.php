<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Ruang;
use App\Models\Periode;
use App\Models\Sidang;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Users ──────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );
        User::firstOrCreate(
            ['email' => 'koordinator@skripsi.ac.id'],
            ['name' => 'Dr. Koordinator Skripsi', 'password' => bcrypt('password'), 'role' => User::ROLE_KOORDINATOR]
        );
        User::firstOrCreate(
            ['email' => 'mahasiswa@skripsi.ac.id'],
            ['name' => 'Ahmad Mahasiswa', 'password' => bcrypt('password'), 'role' => User::ROLE_MAHASISWA, 'nim' => '201951161']
        );

        // ── Sample Dosen ───────────────────────────────────────────────
        $d1 = Dosen::firstOrCreate(['nidn' => '0012058501'], ['nama_dosen' => 'Arief Susanto, ST., M.Kom']);
        $d2 = Dosen::firstOrCreate(['nidn' => '0018088702'], ['nama_dosen' => 'Dr. Rina Fiati, ST., M.Cs']);
        $d3 = Dosen::firstOrCreate(['nidn' => '0025109003'], ['nama_dosen' => 'Ratih Nindyasari, S.Kom., M.Kom']);
        $d4 = Dosen::firstOrCreate(['nidn' => '0031107804'], ['nama_dosen' => 'Aditya Akbar Riadi, S.Kom., M.Kom']);
        $d5 = Dosen::firstOrCreate(['nidn' => '0022098905'], ['nama_dosen' => 'Dr. Anastasya Latubessy, S.Kom., M.Cs']);

        // ── Dosen User Account ──────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'arief@skripsi.ac.id'],
            [
                'name' => 'Arief Susanto, ST., M.Kom',
                'password' => bcrypt('password'),
                'role' => User::ROLE_DOSEN,
                'dosen_id' => $d1->id
            ]
        );

        // ── Sample Ruang ───────────────────────────────────────────────
        $r1 = Ruang::firstOrCreate(['kode_ruangan' => 'J.5.05'], ['nama_ruangan' => 'Ruang J.5.05 (Lt. 5 Gedung J)']);
        $r2 = Ruang::firstOrCreate(['kode_ruangan' => 'J.4.03'], ['nama_ruangan' => 'Ruang J.4.03 (Lt. 4 Gedung J)']);
        $r3 = Ruang::firstOrCreate(['kode_ruangan' => 'J.4.10'], ['nama_ruangan' => 'Ruang J.4.10 (Lt. 4 Gedung J)']);
        $r4 = Ruang::firstOrCreate(['kode_ruangan' => 'J.4.12'], ['nama_ruangan' => 'Ruang J.4.12 (Lt. 4 Gedung J)']);

        // ── Sample Periode ─────────────────────────────────────────────
        $periode = Periode::firstOrCreate(
            ['nama_periode' => 'Semester Gasal 2025/2026'],
            ['aktif' => true]
        );

        // ── Sample Sidang ──────────────────────────────────────────────
        Sidang::firstOrCreate(
            ['nim' => '202251146', 'jenis_tugas_akhir' => 'skripsi'],
            [
                'nama_mahasiswa' => 'HELMI AGHIB RIZKI',
                'judul_skripsi' => 'PROTOTYPE "NGOCEH GO" PENDATAAN KEMISKINAN KELUARGA DI DESA KALIWUNGU BERBASIS SUARA',
                'dosen_pembimbing_utama_id' => $d1->id,
                'dosen_pembimbing_pendamping_id' => $d3->id,
                'ketua_penguji_id' => $d2->id,
                'anggota_penguji_1_id' => $d3->id,
                'anggota_penguji_2_id' => $d1->id,
                'ruang_id' => $r1->id,
                'periode_id' => $periode->id,
                'tanggal' => '2026-07-15',
                'tanggal_pendaftaran' => '2026-07-01',
                'jam' => '08.00 - 09.00',
            ]
        );

        Sidang::firstOrCreate(
            ['nim' => '202251081', 'jenis_tugas_akhir' => 'skripsi'],
            [
                'nama_mahasiswa' => 'MUHAMMAD ROKHIBUL ILMI',
                'judul_skripsi' => 'RANCANG BANGUN APLIKASI TOP-UP GAME BERBASIS WEB MENGGUNAKAN LARAVEL DAN INTEGRASI PAYMENT GATEWAY MIDTRANS',
                'dosen_pembimbing_utama_id' => $d5->id,
                'dosen_pembimbing_pendamping_id' => $d2->id,
                'ketua_penguji_id' => $d4->id,
                'anggota_penguji_1_id' => $d3->id,
                'anggota_penguji_2_id' => null,
                'ruang_id' => $r2->id,
                'periode_id' => $periode->id,
                'tanggal' => '2026-07-15',
                'tanggal_pendaftaran' => '2026-07-02',
                'jam' => '08.00 - 09.00',
            ]
        );

        Sidang::firstOrCreate(
            ['nim' => '202251109', 'jenis_tugas_akhir' => 'jurnal'],
            [
                'nama_mahasiswa' => 'QATRHUNNADA ABIYU AKHDAN',
                'judul_skripsi' => 'WEB-BASED CUSTOMER LOYALTY POINT SYSTEM USING QR CODE WITH WHATSAPP NOTIFICATION',
                'dosen_pembimbing_utama_id' => $d4->id,
                'dosen_pembimbing_pendamping_id' => null,
                'ketua_penguji_id' => $d2->id,
                'anggota_penguji_1_id' => $d1->id,
                'anggota_penguji_2_id' => null,
                'ruang_id' => $r3->id,
                'periode_id' => $periode->id,
                'tanggal' => '2026-07-14',
                'tanggal_pendaftaran' => '2026-07-03',
                'jam' => '11.00 - 11.30',
            ]
        );

        // Seed default system menus and role permissions
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
    }
}
