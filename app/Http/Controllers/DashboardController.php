<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\KesediaanDosen;
use App\Models\Menu;
use App\Models\PendaftaranPeriode;
use App\Models\Periode;
use App\Models\Ruang;
use App\Models\Sidang;
use App\Services\KelulusanService;
use App\Services\SidangConflictService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/**
 * Dashboard Super Admin & Koordinator: ringkasan per periode/gelombang,
 * daftar "perlu tindakan", agenda 7 hari, alur progres, beban dosen, grafik.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isDosen()) {
            return redirect()->route('dosen.dashboard');
        }
        if ($user->isMahasiswa()) {
            return redirect()->route('mahasiswa.dashboard');
        }

        // Ujian yang sudah lewat & belum diisi hasilnya dianggap Lulus (sama seperti halaman jadwal).
        KelulusanService::autoFinalizePastExams();

        $hariIni = now()->timezone('Asia/Jakarta')->startOfDay();

        // ── Filter periode & gelombang ──
        $periodes = Periode::orderByDesc('id')->get();
        $periodeParam = $request->get('periode_id');
        if ($periodeParam === 'semua') {
            $selectedPeriode = null;
        } else {
            $selectedPeriode = $periodeParam
                ? $periodes->firstWhere('id', (int) $periodeParam)
                : ($periodes->firstWhere('aktif', true) ?? $periodes->first());
        }
        $periodeValue = $selectedPeriode?->id ?? 'semua';

        $gelombangOptions = $selectedPeriode
            ? PendaftaranPeriode::where('periode_id', $selectedPeriode->id)->pluck('gelombang')
                ->merge(Sidang::where('periode_id', $selectedPeriode->id)->whereNotNull('gelombang')->distinct()->pluck('gelombang'))
                ->filter(fn ($g) => $g !== null && $g !== '')->map(fn ($g) => (string) $g)->unique()->sort()->values()
            : collect();
        $selectedGelombang = $request->get('gelombang');
        if ($selectedGelombang === '' || !$selectedPeriode) {
            $selectedGelombang = null;
        }

        $sidangs = Sidang::with(['ruang', 'pembimbingUtama', 'ketuaPenguji', 'anggotaPenguji1', 'anggotaPenguji2'])
            ->when($selectedPeriode, fn ($q) => $q->where('periode_id', $selectedPeriode->id))
            ->when($selectedGelombang !== null, fn ($q) => $q->where('gelombang', $selectedGelombang))
            ->get();

        $isSempro = fn ($s) => $s->jenis_tugas_akhir === 'sempro';
        $sempro  = $sidangs->filter($isSempro);
        $skripsi = $sidangs->reject($isSempro);
        $menunggu = fn ($s) => $s->verifikasi_status === 'menunggu' || $s->verifikasi_status === null;

        // ── KPI ──
        $kpi = [
            'pendaftar'        => $sidangs->pluck('nim')->filter()->unique()->count(),
            'sempro'           => $sempro->count(),
            'skripsi'          => $skripsi->count(),
            'disetujui'        => $sidangs->where('verifikasi_status', 'disetujui')->count(),
            'menunggu'         => $sidangs->filter($menunggu)->count(),
            'ditolak'          => $sidangs->where('verifikasi_status', 'ditolak')->count(),
            'terjadwal'        => $sidangs->whereNotNull('tanggal')->count(),
            'belum_terjadwal'  => $sidangs->where('verifikasi_status', 'disetujui')->whereNull('tanggal')->count(),
            'lulus_skripsi'    => $skripsi->where('status_ujian', 'lulus')->count(),
            'tidak_lulus'      => $sidangs->where('status_ujian', 'tidak_lulus')->count(),
        ];

        // ── Alur progres per jenis ──
        $tahap = function (Collection $c) use ($hariIni) {
            $disetujui = $c->where('verifikasi_status', 'disetujui');
            return [
                ['Mendaftar', $c->count()],
                ['Terverifikasi', $disetujui->count()],
                ['Terjadwal', $disetujui->whereNotNull('tanggal')->count()],
                ['Sudah ujian', $c->filter(fn ($s) => $s->tanggal && $s->tanggal->lt($hariIni))->count()],
                ['Lulus', $c->where('status_ujian', 'lulus')->count()],
            ];
        };
        $alur = [
            ['label' => 'Seminar Proposal', 'key' => 'sempro', 'tahap' => $tahap($sempro)],
            ['label' => 'Sidang Skripsi / Jurnal', 'key' => 'skripsi', 'tahap' => $tahap($skripsi)],
        ];

        // ── Agenda 7 hari ke depan ──
        $batasAgenda = $hariIni->copy()->addDays(6)->endOfDay();
        $agenda = $sidangs
            ->filter(fn ($s) => $s->tanggal && $s->tanggal->between($hariIni, $batasAgenda))
            ->sortBy(fn ($s) => $s->tanggal->format('Ymd') . ($this->jamMulai($s->jam) ?? '99:99'))
            ->values();
        $agendaPerTanggal = $agenda->take(12)->groupBy(fn ($s) => $s->tanggal->format('Y-m-d'));

        // ── Perlu tindakan ──
        $bisa = $this->aksesRute($user);
        $q = array_filter(['periode_id' => $selectedPeriode?->id, 'gelombang' => $selectedGelombang], fn ($v) => $v !== null);
        $link = fn (string $route, array $extra = []) => $bisa($route) ? route($route, $q + $extra) : null;

        // Bentrok dicek pada semua jadwal mendatang (lintas periode), karena ruang & dosen dipakai bersama.
        $mendatang = Sidang::with('ruang')->whereNotNull('tanggal')->whereNotNull('jam')
            ->whereDate('tanggal', '>=', $hariIni->format('Y-m-d'))->get();
        $peta = SidangConflictService::detectAllConflicts($mendatang);
        $bentrok = $mendatang->filter(fn ($s) => !empty($peta[$s->id]['schedule']));
        $bentrokSempro = $bentrok->filter($isSempro)->count();
        $bentrokSkripsi = $bentrok->count() - $bentrokSempro;

        $dosenBelumKesediaan = null;
        if ($selectedPeriode && $selectedPeriode->show_form_kesediaan) {
            $sudah = KesediaanDosen::where('periode_id', $selectedPeriode->id)->distinct()->pluck('dosen_id');
            $dosenBelumKesediaan = Dosen::excludingSuperAdminPlaceholder()
                ->where(fn ($w) => $w->whereNull('status_kepegawaian')->orWhere('status_kepegawaian', '!=', Dosen::STATUS_TUGAS_BELAJAR))
                ->whereNotIn('id', $sudah)->count();
        }
        $ruangBelumSiap = Ruang::where('status', '!=', Ruang::STATUS_SIAP)->count();

        $tindakan = collect([
            ['Verifikasi berkas Sempro', $sempro->filter($menunggu)->count(), 'Pendaftaran menunggu diperiksa', 'shield', 'amber', $link('pendaftaran.sempro', ['verifikasi_status' => 'menunggu'])],
            ['Verifikasi berkas Skripsi', $skripsi->filter($menunggu)->count(), 'Pendaftaran menunggu diperiksa', 'shield', 'amber', $link('pendaftaran.skripsi', ['verifikasi_status' => 'menunggu'])],
            ['Sempro belum dijadwalkan', $sempro->where('verifikasi_status', 'disetujui')->whereNull('tanggal')->count(), 'Berkas disetujui, jadwal belum diplot', 'calendar', 'sky', $link('jadwal-sempro.index', ['status' => 'belum'])],
            ['Skripsi belum dijadwalkan', $skripsi->where('verifikasi_status', 'disetujui')->whereNull('tanggal')->count(), 'Berkas disetujui, jadwal belum diplot', 'calendar', 'sky', $link('jadwal-ujian.index', ['status' => 'belum'])],
            ['Jadwal Sempro bentrok', $bentrokSempro, 'Ruang atau dosen dipakai bersamaan', 'warning', 'rose', $bisa('jadwal-sempro.index') ? route('jadwal-sempro.index') : null],
            ['Jadwal Skripsi bentrok', $bentrokSkripsi, 'Ruang atau dosen dipakai bersamaan', 'warning', 'rose', $bisa('jadwal-ujian.index') ? route('jadwal-ujian.index') : null],
            ['Dosen belum isi kesediaan', (int) $dosenBelumKesediaan, 'Form kesediaan periode ini masih dibuka', 'users', 'violet', $bisa('master.kesediaan-dosen.index') ? route('master.kesediaan-dosen.index') : null],
            ['Ruang belum siap digunakan', $ruangBelumSiap, 'Tidak muncul di pilihan penjadwalan', 'building', 'slate', $bisa('master.ruang.index') ? route('master.ruang.index') : null],
        ])->map(fn ($t) => array_combine(['judul', 'jumlah', 'ket', 'ikon', 'warna', 'url'], $t))
          ->filter(fn ($t) => $t['jumlah'] > 0)->values();

        // ── Beban dosen (bimbingan + menguji, aturan sama dengan halaman Analitik) ──
        $beban = [];
        foreach ($sidangs as $s) {
            $pembimbing = array_filter([$s->dosen_pembimbing_utama_id, $s->dosen_pembimbing_pendamping_id]);
            foreach ($pembimbing as $id) {
                $beban[$id]['bimbing'] = ($beban[$id]['bimbing'] ?? 0) + 1;
            }
            foreach (array_unique(array_filter([$s->ketua_penguji_id, $s->anggota_penguji_1_id, $s->anggota_penguji_2_id])) as $id) {
                if (!in_array($id, $pembimbing)) {
                    $beban[$id]['menguji'] = ($beban[$id]['menguji'] ?? 0) + 1;
                }
            }
        }
        $namaDosen = Dosen::withTrashed()->excludingSuperAdminPlaceholder()->whereIn('id', array_keys($beban))->pluck('nama_dosen', 'id');
        $bebanDosen = collect($beban)
            ->filter(fn ($v, $id) => $namaDosen->has($id))
            ->map(fn ($v, $id) => [
                'nama'    => $namaDosen[$id],
                'bimbing' => $v['bimbing'] ?? 0,
                'menguji' => $v['menguji'] ?? 0,
                'total'   => ($v['bimbing'] ?? 0) + ($v['menguji'] ?? 0),
            ])->sortByDesc('total')->values();
        $rataBeban = $bebanDosen->count() ? round($bebanDosen->avg('total'), 1) : 0;
        $maxBeban = max(1, (int) $bebanDosen->max('total'));

        // ── Grafik ──
        $jalur = [
            'sempro_reguler' => $sempro->where('jalur_ta', 'sidang')->count(),
            'sempro_jurnal'  => $sempro->where('jalur_ta', 'jurnal')->count(),
            'sempro_belum'   => $sempro->whereNull('jalur_ta')->count(),
            'skripsi_reguler' => $skripsi->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang'])->count(),
            'skripsi_jurnal' => $skripsi->where('jenis_tugas_akhir', 'jurnal')->count(),
        ];

        // Lulusan per tahun: hanya sidang skripsi/jurnal berstatus LULUS (lintas periode).
        $lulusanPerTahun = Sidang::selectRaw('YEAR(tanggal) as tahun, COUNT(DISTINCT nim) as total')
            ->whereIn('jenis_tugas_akhir', Sidang::SKRIPSI_BUCKET)
            ->where('status_ujian', 'lulus')
            ->whereNotNull('tanggal')
            ->groupBy('tahun')->orderBy('tahun')->get();
        if ($lulusanPerTahun->isEmpty()) {
            $lulusanPerTahun = collect([(object) ['tahun' => (int) date('Y'), 'total' => 0]]);
        }

        $terbaru = $sidangs->sortByDesc('id')->take(6)->values();

        // Agenda terdekat untuk kartu sambutan.
        $berikut = $agenda->first() ?? $sidangs->filter(fn ($s) => $s->tanggal && $s->tanggal->gte($hariIni))
            ->sortBy(fn ($s) => $s->tanggal->format('Ymd') . ($this->jamMulai($s->jam) ?? '99:99'))->first();
        $agendaSambutan = $berikut ? [
            'jenis'   => $berikut->jenis_tugas_akhir === 'sempro' ? 'Seminar Proposal' : ($berikut->jenis_tugas_akhir === 'jurnal' ? 'Sidang Jurnal' : 'Sidang Skripsi'),
            'judul'   => $berikut->nama_mahasiswa,
            'sub'     => $berikut->judul_skripsi,
            'tanggal' => $berikut->tanggal,
            'jam'     => $berikut->jam ? $berikut->jam . ' WIB' : null,
            'ruang'   => $berikut->ruang?->kode_ruangan,
            'url'     => $bisa('jadwal.semua.index') ? route('jadwal.semua.index') : null,
        ] : null;

        return view('dashboard', compact(
            'periodes', 'selectedPeriode', 'periodeValue', 'gelombangOptions', 'selectedGelombang',
            'kpi', 'alur', 'agenda', 'agendaPerTanggal', 'tindakan', 'bebanDosen', 'rataBeban', 'maxBeban',
            'jalur', 'lulusanPerTahun', 'terbaru', 'agendaSambutan', 'hariIni'
        ) + ['bisa' => $bisa]);
    }

    /** "08.00 - 09.00" → "08:00" untuk pengurutan. */
    private function jamMulai(?string $jam): ?string
    {
        $r = SidangConflictService::parseJamRange($jam);
        return $r ? sprintf('%02d:%02d', intdiv($r['start'], 60), $r['start'] % 60) : null;
    }

    /**
     * Rute boleh ditautkan bila ada & (super admin / bukan menu terkelola / menu diizinkan) —
     * sama dengan aturan middleware menu.permission.
     */
    private function aksesRute($user): \Closure
    {
        $menuAktif = Menu::where('is_active', true)->pluck('route')->filter()->all();
        return fn (string $route) => Route::has($route)
            && ($user->isSuperAdmin() || !in_array($route, $menuAktif, true) || $user->hasMenuAccess($route));
    }
}
