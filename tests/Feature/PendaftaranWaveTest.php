<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Periode;
use App\Models\PendaftaranPeriode;
use App\Models\Dosen;
use App\Models\Sidang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PendaftaranWaveTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure default menus are seeded for routing middleware permission checks
        $menuController = new \App\Http\Controllers\MenuController();
        $menuController->ensureDefaultMenusExist();

        User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );
        User::firstOrCreate(
            ['email' => 'mahasiswa@skripsi.ac.id'],
            ['name' => 'Ahmad Mahasiswa', 'password' => bcrypt('password'), 'role' => User::ROLE_MAHASISWA]
        );
        Periode::firstOrCreate(
            ['nama_periode' => 'Semester Gasal 2025/2026'],
            ['aktif' => true]
        );
        Dosen::firstOrCreate(
            ['nidn' => '0012058501'],
            ['nama_dosen' => 'Arief Susanto, ST., M.Kom']
        );
    }

    public function test_super_admin_can_manage_registration_waves(): void
    {
        $admin = User::where('email', 'admin@skripsi.ac.id')->first();
        $this->assertNotNull($admin);

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        // 1. Create a wave
        $response = $this->actingAs($admin)
            ->post(route('master.pendaftaran-periode.store'), [
                'periode_id' => $activePeriode->id,
                'jenis' => 'sempro',
                'gelombang' => 2,
                'tanggal_mulai' => now()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(7)->format('Y-m-d'),
            ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        $wave = PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', 'sempro')
            ->where('gelombang', 2)
            ->first();
        $this->assertNotNull($wave);

        // 2. Edit a wave
        $response = $this->actingAs($admin)
            ->put(route('master.pendaftaran-periode.update', $wave), [
                'periode_id' => $activePeriode->id,
                'jenis' => 'sempro',
                'gelombang' => 2,
                'tanggal_mulai' => now()->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(14)->format('Y-m-d'), // Change range
            ], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals(now()->addDays(14)->format('Y-m-d'), $wave->fresh()->tanggal_selesai->format('Y-m-d'));

        // 3. Delete a wave
        $response = $this->actingAs($admin)
            ->delete(route('master.pendaftaran-periode.destroy', $wave), [], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertNull($wave->fresh());
    }

    public function test_student_registration_respects_waves(): void
    {
        $student = User::where('email', 'mahasiswa@skripsi.ac.id')->first();
        $this->assertNotNull($student);
        $student->update(['nim' => '202451000']);

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        $dosen = Dosen::first();
        $this->assertNotNull($dosen);

        // Tutup semua gelombang Sempro di periode aktif.
        PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', 'sempro')
            ->delete();

        $form = fn () => [
            'nim' => '202451000',
            'jenis_tugas_akhir' => 'sempro',
            'judul_skripsi' => 'Pengembangan Sistem Pengingat Sidang',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'no_wa_aktif' => '081234567890',
            'file_persyaratan' => \Illuminate\Http\UploadedFile::fake()->create('persyaratan.pdf', 500, 'application/pdf'),
        ];

        // 1. Gelombang TUTUP → ditolak.
        $this->actingAs($student)->post(route('mahasiswa.daftar.store'), $form())
            ->assertSessionHas('error');
        $this->assertFalse(Sidang::where('nim', '202451000')->where('jenis_tugas_akhir', 'sempro')->exists());

        // 2. Buka gelombang hari ini.
        PendaftaranPeriode::create([
            'periode_id' => $activePeriode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->subDays(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
        ]);

        // 3. Gelombang BUKA → berhasil, berkas tersimpan di penyimpanan privat.
        $this->actingAs($student)->post(route('mahasiswa.daftar.store'), $form())
            ->assertSessionHas('success');
        $sidang = Sidang::where('nim', '202451000')->where('jenis_tugas_akhir', 'sempro')->first();
        $this->assertNotNull($sidang);
        $this->assertStringStartsWith('berkas/persyaratan/', $sidang->file_persyaratan);
        $this->assertFileExists(\App\Support\BerkasMahasiswa::lokasi($sidang->file_persyaratan));
        $this->assertStringStartsWith(storage_path(), \App\Support\BerkasMahasiswa::lokasi($sidang->file_persyaratan));

        \App\Support\BerkasMahasiswa::hapus($sidang->file_persyaratan);
    }

    public function test_super_admin_can_verify_registrations(): void
    {
        $admin = User::where('email', 'admin@skripsi.ac.id')->first();
        $this->assertNotNull($admin);

        $dosen = Dosen::first();
        $sidang = Sidang::firstOrCreate(
            ['nim' => '202451001', 'jenis_tugas_akhir' => 'sempro'],
            ['nama_mahasiswa' => 'Test Student', 'judul_skripsi' => 'Test Proposal', 'verifikasi_status' => 'menunggu', 'dosen_pembimbing_utama_id' => $dosen->id]
        );
        $this->assertNotNull($sidang);

        // 1. Verify/Approve the registration
        $response = $this->actingAs($admin)
            ->post(route('master.sidang.verifikasi', $sidang), [
                'verifikasi_status' => 'disetujui',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('disetujui', $sidang->fresh()->verifikasi_status);

        // 2. Reject the registration with comment
        $response = $this->actingAs($admin)
            ->post(route('master.sidang.verifikasi', $sidang), [
                'verifikasi_status' => 'ditolak',
                'verifikasi_komentar' => 'Berkas Transkrip Nilai belum diunggah.',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('ditolak', $sidang->fresh()->verifikasi_status);
        $this->assertEquals('Berkas Transkrip Nilai belum diunggah.', $sidang->fresh()->verifikasi_komentar);
    }

    public function test_student_can_revise_rejected_registration(): void
    {
        $student = User::where('email', 'mahasiswa@skripsi.ac.id')->first();
        $this->assertNotNull($student);
        $student->update(['nim' => '202451099']);

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        $dosen = Dosen::first();
        $this->assertNotNull($dosen);

        PendaftaranPeriode::updateOrCreate([
            'periode_id' => $activePeriode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
        ], [
            'tanggal_mulai' => now()->subDays(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
        ]);

        $sidang = Sidang::create([
            'nim' => '202451099',
            'nama_mahasiswa' => $student->name,
            'judul_skripsi' => 'Judul Awal',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'jenis_tugas_akhir' => 'sempro',
            'periode_id' => $activePeriode->id,
            'tanggal_pendaftaran' => now()->subDays(1),
            'verifikasi_status' => 'ditolak',
            'verifikasi_komentar' => 'Berkas kurang lengkap',
        ]);

        $this->actingAs($student)->post(route('mahasiswa.daftar.store'), [
            'nim' => '202451099',
            'jenis_tugas_akhir' => 'sempro',
            'judul_skripsi' => 'Judul Revisi Yang Benar',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'no_wa_aktif' => '081234567890',
            'file_persyaratan' => \Illuminate\Http\UploadedFile::fake()->create('revisi.pdf', 300, 'application/pdf'),
        ])->assertSessionHas('success');

        $fresh = $sidang->fresh();
        $this->assertEquals('JUDUL REVISI YANG BENAR', mb_strtoupper($fresh->judul_skripsi));
        $this->assertEquals('menunggu', $fresh->verifikasi_status);
        $this->assertNull($fresh->verifikasi_komentar);
        $this->assertTrue(\App\Support\BerkasMahasiswa::ada($fresh->file_persyaratan));

        \App\Support\BerkasMahasiswa::hapus($fresh->file_persyaratan);
    }

    public function test_student_can_update_receipt_only(): void
    {
        $student = User::where('email', 'mahasiswa@skripsi.ac.id')->first();
        $this->assertNotNull($student);
        $student->update(['nim' => '202451098']);

        $activePeriode = Periode::where('aktif', true)->first();
        $dosen = Dosen::first();

        $sidang = Sidang::create([
            'nim' => '202451098',
            'nama_mahasiswa' => $student->name,
            'judul_skripsi' => 'Judul Test',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'jenis_tugas_akhir' => 'sempro',
            'periode_id' => $activePeriode->id,
            'tanggal_pendaftaran' => now(),
            'verifikasi_status' => 'ditolak',
            'verifikasi_komentar' => 'Komentar salah',
        ]);

        // Hanya mengganti berkas persyaratan (tanpa mengisi ulang formulir).
        $this->actingAs($student)->post(route('mahasiswa.sidang.update-bukti', $sidang), [
            'file_persyaratan' => \Illuminate\Http\UploadedFile::fake()->create('revisi.pdf', 400, 'application/pdf'),
        ])->assertSessionHas('success');

        $fresh = $sidang->fresh();
        $this->assertEquals('menunggu', $fresh->verifikasi_status);
        $this->assertNull($fresh->verifikasi_komentar);
        $this->assertStringStartsWith('berkas/persyaratan/', $fresh->file_persyaratan);
        $this->assertTrue(\App\Support\BerkasMahasiswa::ada($fresh->file_persyaratan));

        // Mahasiswa lain tidak boleh mengganti berkas milik orang lain.
        $lain = User::create(['name' => 'Mhs Lain', 'email' => 'lain' . uniqid() . '@test.id', 'password' => bcrypt('x'), 'role' => User::ROLE_MAHASISWA, 'nim' => 'LAIN' . substr(uniqid(), -5)]);
        $this->actingAs($lain)->post(route('mahasiswa.sidang.update-bukti', $sidang), [
            'file_persyaratan' => \Illuminate\Http\UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
        ])->assertForbidden();

        \App\Support\BerkasMahasiswa::hapus($fresh->file_persyaratan);
    }

    public function test_excel_import_automatically_verifies_registrations(): void
    {
        $admin = User::where('email', 'admin@skripsi.ac.id')->first();
        $this->assertNotNull($admin);

        $filePath = 'e:\\skripsi\\Sempro Desember (5).xlsx';
        if (!file_exists($filePath)) {
            $this->markTestSkipped('Sempro Desember (5).xlsx not found.');
            return;
        }

        // Snapshot IDs before import
        $idsBefore = Sidang::pluck('id')->toArray();

        $file = new \Illuminate\Http\UploadedFile(
            $filePath,
            'Sempro Desember (5).xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)
            ->post(route('master.sempro.import'), [
                'excel_file' => $file,
            ]);

        $response->assertSessionHas('success');

        // Only check records created during this import
        $imported = Sidang::whereNotIn('id', $idsBefore)
            ->where('jenis_tugas_akhir', 'sempro')
            ->get();

        $this->assertNotEmpty($imported, 'No new sempro records were created by the import.');
        foreach ($imported as $sidang) {
            $this->assertEquals('disetujui', $sidang->verifikasi_status,
                "NIM {$sidang->nim} should have status 'disetujui' after import.");
            $this->assertNotNull($sidang->verifikasi_tanggal,
                "NIM {$sidang->nim} should have a verifikasi_tanggal after import.");
        }
    }
}
