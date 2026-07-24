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
            ->put(route('master.pendaftaran-periode.update', $wave->id), [
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
            ->delete(route('master.pendaftaran-periode.destroy', $wave->id), [], ['Accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertNull($wave->fresh());
    }

    public function test_student_registration_respects_waves(): void
    {
        $student = User::where('email', 'mahasiswa@skripsi.ac.id')->first();
        $this->assertNotNull($student);

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        // Get a sample lecturer to be selected as pembimbing
        $dosen = Dosen::first();
        $this->assertNotNull($dosen);

        // Delete all registration waves of jenis sempro in the active period to simulate closed registration
        PendaftaranPeriode::where('periode_id', $activePeriode->id)
            ->where('jenis', 'sempro')
            ->delete();

        // 1. Submit registration when wave is CLOSED - should return error
        $response = $this->actingAs($student)
            ->post(route('mahasiswa.daftar.store'), [
                'nim' => '202451000',
                'jenis_tugas_akhir' => 'sempro',
                'judul_skripsi' => 'Pengembangan Sistem Pengingat Sidang',
                'dosen_pembimbing_utama_id' => $dosen->id,
                'bukti_pembayaran' => \Illuminate\Http\UploadedFile::fake()->create('receipt.pdf', 500),
            ]);

        $response->assertSessionHas('error');
        $this->assertFalse(Sidang::where('nim', '202451000')->where('jenis_tugas_akhir', 'sempro')->exists());

        // 2. Create an active wave for sempro today
        PendaftaranPeriode::create([
            'periode_id' => $activePeriode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
            'tanggal_mulai' => now()->subDays(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
        ]);

        // 3. Submit registration when wave is OPEN - should succeed
        $response = $this->actingAs($student)
            ->post(route('mahasiswa.daftar.store'), [
                'nim' => '202451000',
                'jenis_tugas_akhir' => 'sempro',
                'judul_skripsi' => 'Pengembangan Sistem Pengingat Sidang',
                'dosen_pembimbing_utama_id' => $dosen->id,
                'bukti_pembayaran' => \Illuminate\Http\UploadedFile::fake()->create('receipt.pdf', 500),
            ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Sidang::where('nim', '202451000')->where('jenis_tugas_akhir', 'sempro')->exists());
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
            ->post(route('master.sidang.verifikasi', $sidang->id), [
                'verifikasi_status' => 'disetujui',
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals('disetujui', $sidang->fresh()->verifikasi_status);

        // 2. Reject the registration with comment
        $response = $this->actingAs($admin)
            ->post(route('master.sidang.verifikasi', $sidang->id), [
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

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        $dosen = Dosen::first();
        $this->assertNotNull($dosen);

        // Make sure there is an open wave
        PendaftaranPeriode::updateOrCreate([
            'periode_id' => $activePeriode->id,
            'jenis' => 'sempro',
            'gelombang' => 1,
        ], [
            'tanggal_mulai' => now()->subDays(2)->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
        ]);

        // Create a rejected registration record
        $sidang = Sidang::create([
            'nim' => '202451099',
            'nama_mahasiswa' => $student->name,
            'judul_skripsi' => 'Judul Awal',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'jenis_tugas_akhir' => 'sempro',
            'periode_id' => $activePeriode->id,
            'tanggal_pendaftaran' => now()->subDays(1),
            'verifikasi_status' => 'ditolak',
            'verifikasi_komentar' => 'Salah bukti bayar',
            'bukti_pembayaran' => 'uploads/bukti_pembayaran/old.pdf',
        ]);

        // Submit revision with updated title, new file upload should overwrite/revise
        $response = $this->actingAs($student)
            ->post(route('mahasiswa.daftar.store'), [
                'nim' => '202451099',
                'jenis_tugas_akhir' => 'sempro',
                'judul_skripsi' => 'Judul Revisi Yang Benar',
                'dosen_pembimbing_utama_id' => $dosen->id,
                'bukti_pembayaran' => \Illuminate\Http\UploadedFile::fake()->create('new_receipt.png', 300),
            ]);

        $response->assertSessionHas('success');
        
        $fresh = $sidang->fresh();
        $this->assertEquals('Judul Revisi Yang Benar', $fresh->judul_skripsi);
        $this->assertEquals('menunggu', $fresh->verifikasi_status);
        $this->assertNull($fresh->verifikasi_komentar);
        $this->assertStringEndsWith('.png', $fresh->bukti_pembayaran);
        $this->assertTrue(file_exists(public_path($fresh->bukti_pembayaran)));
        
        // Clean up uploaded file if it was created
        if (file_exists(public_path($fresh->bukti_pembayaran))) {
            @unlink(public_path($fresh->bukti_pembayaran));
        }
    }

    public function test_student_can_update_receipt_only(): void
    {
        $student = User::where('email', 'mahasiswa@skripsi.ac.id')->first();
        $this->assertNotNull($student);

        $activePeriode = Periode::where('aktif', true)->first();
        $this->assertNotNull($activePeriode);

        $dosen = Dosen::first();
        $this->assertNotNull($dosen);

        // Create a rejected registration record with old file path
        $sidang = Sidang::create([
            'nim' => $student->nim ?? '202451099',
            'nama_mahasiswa' => $student->name,
            'judul_skripsi' => 'Judul Test',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'jenis_tugas_akhir' => 'sempro',
            'periode_id' => $activePeriode->id,
            'tanggal_pendaftaran' => now(),
            'verifikasi_status' => 'ditolak',
            'verifikasi_komentar' => 'Komentar salah',
            'bukti_pembayaran' => 'uploads/bukti_pembayaran/old.pdf',
        ]);

        $response = $this->actingAs($student)
            ->post(route('mahasiswa.sidang.update-bukti', $sidang->id), [
                'bukti_pembayaran' => \Illuminate\Http\UploadedFile::fake()->create('revisi.png', 400),
            ]);

        $response->assertSessionHas('success');

        $fresh = $sidang->fresh();
        $this->assertEquals('menunggu', $fresh->verifikasi_status);
        $this->assertNull($fresh->verifikasi_komentar);
        $this->assertStringEndsWith('.png', $fresh->bukti_pembayaran);
        $this->assertTrue(file_exists(public_path($fresh->bukti_pembayaran)));

        // Clean up
        if (file_exists(public_path($fresh->bukti_pembayaran))) {
            @unlink(public_path($fresh->bukti_pembayaran));
        }
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
