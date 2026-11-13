<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KalenderDosenIcsTest extends TestCase
{
    use DatabaseTransactions;

    private Dosen $dosen;
    private Dosen $lain;
    private User $user;
    private Periode $periode;
    private Ruang $ruang;

    protected function setUp(): void
    {
        parent::setUp();
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $this->dosen = Dosen::create(['nama_dosen' => 'Dosen Ics, M.Kom', 'nidn' => 'ICS' . substr(uniqid(), -7)]);
        $this->lain = Dosen::create(['nama_dosen' => 'Dosen Lain Ics', 'nidn' => 'ICL' . substr(uniqid(), -7)]);
        $this->user = User::create(['name' => 'Dosen Ics', 'email' => 'ics' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_DOSEN, 'dosen_id' => $this->dosen->id]);
        $this->periode = Periode::create(['nama_periode' => 'Periode Ics ' . uniqid(), 'aktif' => false]);
        $this->ruang = Ruang::create(['kode_ruangan' => 'IC' . substr(uniqid(), -4), 'nama_ruangan' => 'Lab Ics', 'status' => Ruang::STATUS_SIAP]);
    }

    private function sidang(array $atribut): Sidang
    {
        return Sidang::create($atribut + [
            'nim' => 'ICS' . substr(uniqid(), -7), 'judul_skripsi' => 'Judul, dengan; tanda baca',
            'jenis_tugas_akhir' => 'sidang', 'verifikasi_status' => 'disetujui', 'periode_id' => $this->periode->id,
            'tanggal_pendaftaran' => now()->toDateString(),
        ]);
    }

    public function test_feed_ics_berisi_jadwal_dosen_dan_bisa_diganti_linknya(): void
    {
        $tgl = now()->addDays(7)->toDateString();
        $this->sidang(['nama_mahasiswa' => 'Mhs Diuji', 'ketua_penguji_id' => $this->dosen->id, 'dosen_pembimbing_utama_id' => $this->lain->id,
            'tanggal' => $tgl, 'jam' => '08.00 - 09.30', 'ruang_id' => $this->ruang->id]);
        $this->sidang(['nama_mahasiswa' => 'Mhs Ditolak', 'ketua_penguji_id' => $this->dosen->id, 'verifikasi_status' => 'ditolak',
            'tanggal' => $tgl, 'jam' => '10.00 - 11.00']);
        $this->sidang(['nama_mahasiswa' => 'Mhs Orang Lain', 'ketua_penguji_id' => $this->lain->id,
            'tanggal' => $tgl, 'jam' => '13.00 - 14.00']);
        // Sempro berjadwal terpisah: pendamping mendapat sesi pendampingnya sendiri.
        $tglPendamping = now()->addDays(9)->toDateString();
        $this->sidang(['nama_mahasiswa' => 'Mhs Sempro Terpisah', 'jenis_tugas_akhir' => 'sempro',
            'dosen_pembimbing_utama_id' => $this->lain->id, 'dosen_pembimbing_pendamping_id' => $this->dosen->id,
            'tanggal' => $tgl, 'jam' => '14.00 - 15.00', 'jadwal_terpisah' => true,
            'tanggal_pendamping' => $tglPendamping, 'jam_pendamping' => '09.00 - 10.00']);

        $halaman = $this->actingAs($this->user)->get(route('dosen.kalender'))->assertOk()->assertSee('Tambah ke Google Calendar');
        $token = $this->dosen->fresh()->token_kalender;
        $this->assertSame(40, strlen($token));
        $halaman->assertSee(route('public.dosen.kalender-ics', $token));

        auth()->logout();
        $res = $this->get(route('public.dosen.kalender-ics', $token))->assertOk();
        $this->assertStringStartsWith('text/calendar', $res->headers->get('Content-Type'));
        $ics = $res->getContent();
        $polos = str_replace("\r\n ", '', $ics); // buka lipatan baris

        $this->assertStringContainsString("BEGIN:VCALENDAR\r\n", $ics);
        $this->assertSame(2, substr_count($ics, 'BEGIN:VEVENT'));
        $this->assertStringContainsString('SUMMARY:Sidang Skripsi: MHS DIUJI (Ketua Penguji)', $polos);
        // 08.00 WIB = 01.00 UTC
        $this->assertStringContainsString('DTSTART:' . str_replace('-', '', $tgl) . 'T010000Z', $polos);
        $this->assertStringContainsString('Judul: JUDUL\\, DENGAN\\; TANDA BACA', $polos);
        $this->assertStringNotContainsString('DITOLAK', $polos);
        $this->assertStringNotContainsString('ORANG LAIN', $polos);
        // 09.00 WIB sesi pendamping = 02.00 UTC
        $this->assertStringContainsString('DTSTART:' . str_replace('-', '', $tglPendamping) . 'T020000Z', $polos);
        foreach (explode("\r\n", $ics) as $baris) {
            $this->assertLessThanOrEqual(75, strlen($baris));
        }

        // Ganti link: link lama mati, link baru jalan.
        $this->actingAs($this->user)->post(route('dosen.kalender.ganti-link'))->assertRedirect(route('dosen.kalender'));
        $baru = $this->dosen->fresh()->token_kalender;
        $this->assertNotSame($token, $baru);
        auth()->logout();
        $this->get(route('public.dosen.kalender-ics', $token))->assertNotFound();
        $this->get(route('public.dosen.kalender-ics', $baru))->assertOk();
        $this->get('/kalender/dosen/pendek.ics')->assertNotFound();

        // Token tidak ikut ter-serialize.
        $this->assertArrayNotHasKey('token_kalender', $this->dosen->fresh()->toArray());
    }
}
