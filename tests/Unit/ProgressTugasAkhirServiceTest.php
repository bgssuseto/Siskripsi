<?php

namespace Tests\Unit;

use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Services\ProgressTugasAkhirService as Progress;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Logika murni 5 tahap progress (tanpa query DB): semua relasi di-set manual.
 */
class ProgressTugasAkhirServiceTest extends TestCase
{
    private const WA_SEMPRO  = 'https://chat.whatsapp.com/sempro';
    private const WA_SKRIPSI = 'https://chat.whatsapp.com/skripsi';

    private function today(): Carbon
    {
        return Carbon::parse('2026-10-05', 'Asia/Jakarta');
    }

    private function sidang(array $attrs = [], bool $withLinks = true): Sidang
    {
        $sidang = new Sidang(array_merge([
            'nim'                 => '202251001',
            'jenis_tugas_akhir'   => 'sempro',
            'verifikasi_status'   => 'disetujui',
            'tanggal_pendaftaran' => '2026-09-20',
        ], $attrs));

        $sidang->setRelation('periode', new Periode($withLinks ? [
            'link_grup_wa_sempro'  => self::WA_SEMPRO,
            'link_grup_wa_skripsi' => self::WA_SKRIPSI,
        ] : []));
        $sidang->setRelation('ruang', new Ruang(['kode_ruangan' => 'R101']));

        return $sidang;
    }

    private function statuses(array $progress): array
    {
        return array_column($progress['steps'], 'status', 'key');
    }

    public function test_belum_mendaftar_hanya_pendaftaran_yang_aktif(): void
    {
        $p = Progress::build(null, 'sempro', $this->today());

        $this->assertFalse($p['registered']);
        $this->assertSame([
            'pendaftaran' => 'active', 'verifikasi' => 'pending', 'join_wa' => 'pending',
            'penjadwalan' => 'pending', 'ujian' => 'pending',
        ], $this->statuses($p));
        $this->assertSame('daftar', $p['steps'][0]['cta']);
        $this->assertSame(0, $p['percent']);
        $this->assertSame(0, $p['current_index']);
    }

    public function test_menunggu_verifikasi(): void
    {
        $p = Progress::build($this->sidang(['verifikasi_status' => 'menunggu']), 'sempro', $this->today());

        $this->assertSame('done', $p['steps'][0]['status']);
        $this->assertSame('active', $p['steps'][1]['status']);
        $this->assertSame('pending', $p['steps'][2]['status']);
        $this->assertSame(20, $p['percent']);
        $this->assertSame(1, $p['current_index']);
        $this->assertFalse($p['failed']);
    }

    public function test_verifikasi_status_kosong_dianggap_menunggu(): void
    {
        $p = Progress::build($this->sidang(['verifikasi_status' => null]), 'sempro', $this->today());

        $this->assertSame('active', $p['steps'][1]['status']);
    }

    public function test_berkas_ditolak_menghentikan_tahap_berikutnya(): void
    {
        $p = Progress::build($this->sidang([
            'verifikasi_status'   => 'ditolak',
            'verifikasi_komentar' => 'PDF tidak terbaca',
            // walau data jadwal ada, tidak boleh dianggap terjadwal sebelum berkas diterima
            'tanggal' => '2026-10-10', 'ruang_id' => 1,
        ]), 'sempro', $this->today());

        $this->assertSame('failed', $p['steps'][1]['status']);
        $this->assertSame('revisi', $p['steps'][1]['cta']);
        $this->assertStringContainsString('PDF tidak terbaca', $p['steps'][1]['note']);
        $this->assertSame(['pending', 'pending', 'pending'], array_column(array_slice($p['steps'], 2), 'status'));
        $this->assertTrue($p['failed']);
        $this->assertSame(1, $p['current_index']);
        $this->assertSame(20, $p['percent']);
    }

    public function test_diterima_belum_join_wa_dan_belum_dijadwalkan(): void
    {
        $p = Progress::build($this->sidang(), 'sempro', $this->today());

        $this->assertSame('done', $p['steps'][1]['status']);
        $this->assertSame('active', $p['steps'][2]['status']);
        $this->assertSame('join_wa', $p['steps'][2]['cta']);
        $this->assertSame('active', $p['steps'][3]['status']);
        $this->assertSame('Sedang dijadwalkan', $p['steps'][3]['detail']);
        $this->assertSame('pending', $p['steps'][4]['status']);
        $this->assertSame(40, $p['percent']);
        $this->assertSame(2, $p['current_index']);
    }

    public function test_klik_join_wa_menandai_tahap_selesai(): void
    {
        $p = Progress::build($this->sidang(['wa_joined_at' => '2026-10-03 10:00:00']), 'sempro', $this->today());

        $this->assertSame('done', $p['steps'][2]['status']);
        $this->assertStringContainsString('Bergabung', $p['steps'][2]['detail']);
        $this->assertNull($p['steps'][2]['cta']);
        $this->assertSame('active', $p['steps'][3]['status']);
        $this->assertSame(60, $p['percent']);
        $this->assertSame(3, $p['current_index']);
    }

    public function test_tanpa_link_grup_tahap_join_wa_tidak_bisa_diklik(): void
    {
        $p = Progress::build($this->sidang([], withLinks: false), 'sempro', $this->today());

        $this->assertSame('pending', $p['steps'][2]['status']);
        $this->assertSame('Link grup belum tersedia', $p['steps'][2]['detail']);
        $this->assertNull($p['steps'][2]['cta']);
    }

    public function test_skripsi_memakai_link_grup_skripsi_bukan_sempro(): void
    {
        $sidang = $this->sidang(['jenis_tugas_akhir' => 'sidang']);
        $sidang->periode->link_grup_wa_sempro = null; // hanya link skripsi yang diisi

        $p = Progress::build($sidang, 'skripsi', $this->today());

        $this->assertSame('active', $p['steps'][2]['status']);
        $this->assertSame('join_wa', $p['steps'][2]['cta']);
    }

    public function test_skripsi_belum_terjadwal_sebelum_ketua_penguji_ditentukan(): void
    {
        $tanpaKetua = $this->sidang(['jenis_tugas_akhir' => 'sidang', 'tanggal' => '2026-10-10', 'ruang_id' => 1]);
        $denganKetua = $this->sidang(['jenis_tugas_akhir' => 'sidang', 'tanggal' => '2026-10-10', 'ruang_id' => 1, 'ketua_penguji_id' => 7]);

        $this->assertSame('active', Progress::build($tanpaKetua, 'skripsi', $this->today())['steps'][3]['status']);
        $this->assertSame('done', Progress::build($denganKetua, 'skripsi', $this->today())['steps'][3]['status']);
    }

    public function test_sempro_cukup_tanggal_dan_ruang_untuk_dianggap_terjadwal(): void
    {
        $p = Progress::build($this->sidang(['tanggal' => '2026-10-10', 'ruang_id' => 1]), 'sempro', $this->today());

        $this->assertSame('done', $p['steps'][3]['status']);
        $this->assertSame('Sudah terjadwal', $p['steps'][3]['detail']);
    }

    public function test_tanggal_tanpa_ruang_masih_dijadwalkan(): void
    {
        $p = Progress::build($this->sidang(['tanggal' => '2026-10-10']), 'sempro', $this->today());

        $this->assertSame('active', $p['steps'][3]['status']);
        $this->assertSame('pending', $p['steps'][4]['status']);
    }

    public function test_ujian_mendatang_menampilkan_tanggal_dan_hitung_mundur(): void
    {
        $p = Progress::build(
            $this->sidang(['tanggal' => '2026-10-08', 'ruang_id' => 1, 'jam' => '08.00 - 09.00']),
            'sempro', $this->today()
        );
        $ujian = $p['steps'][4];

        $this->assertSame('active', $ujian['status']);
        $this->assertSame('H-3', $ujian['badge']);
        $this->assertStringContainsString('08 Okt 2026', $ujian['detail']);
        $this->assertSame('Pukul 08.00 - 09.00 • Ruang R101', $ujian['note']);
    }

    public function test_ujian_besok_dan_hari_ini(): void
    {
        $besok = Progress::build($this->sidang(['tanggal' => '2026-10-06', 'ruang_id' => 1]), 'sempro', $this->today());
        $hariIni = Progress::build($this->sidang(['tanggal' => '2026-10-05', 'ruang_id' => 1]), 'sempro', $this->today());

        $this->assertSame('Besok', $besok['steps'][4]['badge']);
        $this->assertSame('active', $hariIni['steps'][4]['status']);
        $this->assertSame('Hari ini', $hariIni['steps'][4]['badge']);
        $this->assertFalse($hariIni['complete']);
    }

    public function test_ujian_sudah_lewat_selesai_dengan_hasil(): void
    {
        $base = ['tanggal' => '2026-10-01', 'ruang_id' => 1, 'wa_joined_at' => '2026-09-25 09:00:00'];

        $lulus   = Progress::build($this->sidang($base + ['status_ujian' => 'lulus']), 'sempro', $this->today());
        $remidi  = Progress::build($this->sidang($base + ['status_ujian' => 'tidak_lulus']), 'sempro', $this->today());
        $belumDiisi = Progress::build($this->sidang($base), 'sempro', $this->today());

        $this->assertSame('done', $lulus['steps'][4]['status']);
        $this->assertSame(['Lulus', 'success'], [$lulus['steps'][4]['badge'], $lulus['steps'][4]['badge_tone']]);
        $this->assertSame(['Tidak lulus', 'danger'], [$remidi['steps'][4]['badge'], $remidi['steps'][4]['badge_tone']]);
        $this->assertSame('Selesai', $belumDiisi['steps'][4]['badge']);
        $this->assertTrue($lulus['complete']);
        $this->assertSame(100, $lulus['percent']);
        $this->assertSame(4, $lulus['current_index']);
    }

    public function test_ujian_lewat_tanpa_join_wa_tetap_selesai_dan_tidak_menawarkan_join(): void
    {
        $p = Progress::build($this->sidang(['tanggal' => '2026-10-01', 'ruang_id' => 1]), 'sempro', $this->today());

        $this->assertSame('pending', $p['steps'][2]['status']);
        $this->assertSame('Tidak tercatat bergabung', $p['steps'][2]['detail']);
        $this->assertNull($p['steps'][2]['cta']);
        $this->assertSame(100, $p['percent']);
        $this->assertSame(4, $p['current_index']);
    }
}
