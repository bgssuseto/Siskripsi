<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\DosenController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\JadwalUjianController;
use App\Http\Controllers\SkripsiController;
use App\Http\Controllers\SemproController;
use App\Http\Controllers\AdministrasiController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\DosenPortalController;
use App\Http\Controllers\KesediaanDosenController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AutoScheduleController;
use App\Http\Controllers\DosenPengujiRuleController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Public Routes (View & Download Jadwal Dosen Tanpa Login)
Route::get('/jadwal-dosen/{token}', [AdministrasiController::class, 'publicJadwalDosen'])->name('public.dosen.jadwal');
Route::get('/jadwal-dosen/{token}/pdf', [AdministrasiController::class, 'publicJadwalDosenPdf'])->name('public.dosen.jadwal.pdf');
Route::get('/jadwal-dosen-penguji', [AdministrasiController::class, 'publicJadwalDosenPenguji'])->name('public.jadwal-dosen-penguji');
Route::get('/jadwal-dosen-penguji/pdf', [AdministrasiController::class, 'publicJadwalDosenPengujiPdf'])->name('public.jadwal-dosen-penguji.pdf');

// Shortlink berbasis alias/inisial dosen (fallback ke token di atas kalau dosen belum punya alias)
Route::get('/j/{alias}', [AdministrasiController::class, 'publicJadwalDosenByAlias'])->name('public.dosen.jadwal.alias');
Route::get('/j/{alias}/pdf', [AdministrasiController::class, 'publicJadwalDosenByAliasPdf'])->name('public.dosen.jadwal.alias.pdf');

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user) {
            if ($user->isDosen()) {
                return redirect()->route('dosen.dashboard');
            }
            if ($user->isMahasiswa()) {
                return redirect()->route('mahasiswa.dashboard');
            }
        }

        // Real Data Metrics
        $totalMahasiswa     = \App\Models\Sidang::distinct('nim')->count('nim');
        $totalSempro        = \App\Models\Sidang::where('jenis_tugas_akhir', 'sempro')->count();
        $totalSkripsi       = \App\Models\Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->count();
        
        $totalBelumPlotting = \App\Models\Sidang::whereNull('tanggal')->count();
        $totalTerjadwal     = \App\Models\Sidang::whereNotNull('tanggal')->count();
        
        $verifikasiMenunggu  = \App\Models\Sidang::where(function($q) {
                                  $q->where('verifikasi_status', 'menunggu')
                                    ->orWhereNull('verifikasi_status');
                              })->count();
        $verifikasiDisetujui = \App\Models\Sidang::where('verifikasi_status', 'disetujui')->count();
        $verifikasiDitolak   = \App\Models\Sidang::where('verifikasi_status', 'ditolak')->count();
        
        $totalDosen = \App\Models\Dosen::count();
        $totalRuang = \App\Models\Ruang::count();
        $totalUser  = \App\Models\User::count();

        // Chart Data 1: Jalur Tugas Akhir — Sempro vs Skripsi, masing-masing dipecah Reguler vs Jurnal
        $skripsiRegulerCount = \App\Models\Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang'])->count();
        $artikelJurnalCount  = \App\Models\Sidang::where('jenis_tugas_akhir', 'jurnal')->count();
        $semproCount         = \App\Models\Sidang::where('jenis_tugas_akhir', 'sempro')->count();

        $semproRegulerCount = \App\Models\Sidang::where('jenis_tugas_akhir', 'sempro')->where('jalur_ta', 'sidang')->count();
        $semproJurnalCount  = \App\Models\Sidang::where('jenis_tugas_akhir', 'sempro')->where('jalur_ta', 'jurnal')->count();
        $semproBelumJalurCount = \App\Models\Sidang::where('jenis_tugas_akhir', 'sempro')->whereNull('jalur_ta')->count();

        $jalurCounts = [
            'Sidang Skripsi (Reguler)' => $skripsiRegulerCount,
            'Artikel Jurnal'           => $artikelJurnalCount,
            'Seminar Proposal'         => $semproCount,
        ];

        // Chart Data 2: Status Verifikasi
        $verifikasiCounts = [
            'Menunggu'  => $verifikasiMenunggu,
            'Disetujui' => $verifikasiDisetujui,
            'Ditolak'   => $verifikasiDitolak,
        ];

        // Chart Data 3: Histogram Lulusan Tiap Tahun
        $yearlyGraduates = \App\Models\Sidang::selectRaw('YEAR(tanggal) as tahun, COUNT(*) as total')
            ->whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])
            ->whereNotNull('tanggal')
            ->where('tanggal', '<=', now()->timezone('Asia/Jakarta')->format('Y-m-d'))
            ->groupBy('tahun')
            ->orderBy('tahun', 'asc')
            ->get();

        if ($yearlyGraduates->isEmpty()) {
            $currYr = (int) date('Y');
            $yearlyGraduates = collect([
                (object)['tahun' => $currYr - 2, 'total' => 0],
                (object)['tahun' => $currYr - 1, 'total' => 0],
                (object)['tahun' => $currYr,     'total' => \App\Models\Sidang::whereIn('jenis_tugas_akhir', ['skripsi', 'sidang', 'jurnal'])->count()],
            ]);
        }

        $recentActivities = \App\Models\Sidang::with(['periode', 'ruang', 'pembimbingUtama'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('dashboard', compact(
            'totalMahasiswa',
            'totalSempro',
            'totalSkripsi',
            'totalBelumPlotting',
            'totalTerjadwal',
            'verifikasiMenunggu',
            'verifikasiDisetujui',
            'verifikasiDitolak',
            'totalDosen',
            'totalRuang',
            'totalUser',
            'skripsiRegulerCount',
            'artikelJurnalCount',
            'semproCount',
            'semproRegulerCount',
            'semproJurnalCount',
            'semproBelumJalurCount',
            'jalurCounts',
            'verifikasiCounts',
            'yearlyGraduates',
            'recentActivities'
        ));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware('signed')->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    // User Management (Super Admin)
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Data Master - Dosen
        Route::get('/master/dosen', [DosenController::class, 'index'])->name('master.dosen.index');
        Route::post('/master/dosen', [DosenController::class, 'store'])->name('master.dosen.store');
        Route::put('/master/dosen/{dosen}', [DosenController::class, 'update'])->name('master.dosen.update');
        Route::delete('/master/dosen/{dosen}', [DosenController::class, 'destroy'])->name('master.dosen.destroy');
        Route::post('/master/dosen/import', [DosenController::class, 'importExcel'])->name('master.dosen.import');
        Route::get('/master/dosen/template', [DosenController::class, 'downloadTemplate'])->name('master.dosen.template');

        // Data Master - Ruang
        Route::get('/master/ruang', [RuangController::class, 'index'])->name('master.ruang.index');
        Route::post('/master/ruang', [RuangController::class, 'store'])->name('master.ruang.store');
        Route::put('/master/ruang/{ruang}', [RuangController::class, 'update'])->name('master.ruang.update');
        Route::delete('/master/ruang/{ruang}', [RuangController::class, 'destroy'])->name('master.ruang.destroy');

        // Data Master - Periode
        Route::get('/master/periode', [PeriodeController::class, 'index'])->name('master.periode.index');
        Route::post('/master/periode', [PeriodeController::class, 'store'])->name('master.periode.store');
        Route::put('/master/periode/{periode}', [PeriodeController::class, 'update'])->name('master.periode.update');
        Route::delete('/master/periode/{periode}', [PeriodeController::class, 'destroy'])->name('master.periode.destroy');
        Route::post('/master/periode/{periode}/active', [PeriodeController::class, 'setActive'])->name('master.periode.active');
        Route::post('/master/pendaftaran-periode', [PeriodeController::class, 'storePendaftaranPeriode'])->name('master.pendaftaran-periode.store');
        Route::put('/master/pendaftaran-periode/{pendaftaranPeriode}', [PeriodeController::class, 'updatePendaftaranPeriode'])->name('master.pendaftaran-periode.update');
        Route::delete('/master/pendaftaran-periode/{pendaftaranPeriode}', [PeriodeController::class, 'destroyPendaftaranPeriode'])->name('master.pendaftaran-periode.destroy');

        // Data Master - Rule Komposisi Dosen Penguji
        Route::get('/master/dosen-penguji-rule', [DosenPengujiRuleController::class, 'index'])->name('master.dosen-penguji-rule.index');
        Route::post('/master/dosen-penguji-rule', [DosenPengujiRuleController::class, 'store'])->name('master.dosen-penguji-rule.store');
        Route::put('/master/dosen-penguji-rule/{dosenPengujiRule}', [DosenPengujiRuleController::class, 'update'])->name('master.dosen-penguji-rule.update');
        Route::delete('/master/dosen-penguji-rule/{dosenPengujiRule}', [DosenPengujiRuleController::class, 'destroy'])->name('master.dosen-penguji-rule.destroy');

        // Data Skripsi (mutasi data tetap super_admin-only; index/export dipindah ke
        // grup di bawah supaya koordinator bisa diberi akses lihat/export saja)
        Route::post('/master/skripsi', [SkripsiController::class, 'store'])->name('master.skripsi.store');
        Route::delete('/master/skripsi/destroy-all', [SkripsiController::class, 'destroyAll'])->name('master.skripsi.destroy-all');
        Route::delete('/master/skripsi/bulk-destroy', [SkripsiController::class, 'bulkDestroy'])->name('master.skripsi.bulk-destroy');
        Route::put('/master/skripsi/{sidang}', [SkripsiController::class, 'update'])->name('master.skripsi.update');
        Route::delete('/master/skripsi/{sidang}', [SkripsiController::class, 'destroy'])->name('master.skripsi.destroy');
        Route::get('/master/skripsi/import', [SkripsiController::class, 'importForm'])->name('master.skripsi.import.form');
        Route::post('/master/skripsi/import', [SkripsiController::class, 'import'])->name('master.skripsi.import');

        // Data Sempro (mutasi data tetap super_admin-only; index/export dipindah ke
        // grup di bawah supaya koordinator bisa diberi akses lihat/export saja)
        Route::post('/master/sempro', [SemproController::class, 'store'])->name('master.sempro.store');
        Route::delete('/master/sempro/destroy-all', [SemproController::class, 'destroyAll'])->name('master.sempro.destroy-all');
        Route::delete('/master/sempro/bulk-destroy', [SemproController::class, 'bulkDestroy'])->name('master.sempro.bulk-destroy');
        Route::put('/master/sempro/{sidang}', [SemproController::class, 'update'])->name('master.sempro.update');
        Route::delete('/master/sempro/{sidang}', [SemproController::class, 'destroy'])->name('master.sempro.destroy');
        Route::get('/master/sempro/import', [SemproController::class, 'importForm'])->name('master.sempro.import.form');
        Route::post('/master/sempro/import', [SemproController::class, 'import'])->name('master.sempro.import');
        Route::post('/master/sidang/{sidang}/verifikasi', [SkripsiController::class, 'verifikasi'])->name('master.sidang.verifikasi');

    });

    // Halaman yang bisa dibuka untuk Koordinator kalau super_admin memberi akses
    // menu-nya lewat Manajemen Menu (route tetap terbuka untuk kedua role, tapi
    // menu.permission menegakkan per-menu access khusus untuk koordinator —
    // super_admin selalu lolos tanpa syarat, lihat CheckMenuPermission).
    Route::middleware(['role:super_admin,koordinator', 'menu.permission'])->group(function () {
        // Data Skripsi & Sempro — lihat & export saja untuk koordinator
        Route::get('/master/skripsi', [SkripsiController::class, 'index'])->name('master.skripsi.index');
        Route::get('/master/skripsi/export', [SkripsiController::class, 'exportExcel'])->name('master.skripsi.export');
        Route::get('/master/sempro', [SemproController::class, 'index'])->name('master.sempro.index');
        Route::get('/master/sempro/export', [SemproController::class, 'exportExcel'])->name('master.sempro.export');

        // Asisten Plotting Jadwal Otomatis
        Route::get('/jadwal/auto-plot', [AutoScheduleController::class, 'index'])->name('jadwal.auto-plot.index');
        Route::post('/jadwal/auto-plot/terapkan', [AutoScheduleController::class, 'apply'])->name('jadwal.auto-plot.apply');

        // Administrasi (semua route di grup ini murni lihat/generate dokumen, tidak
        // ada yang menghapus/mengubah data master, jadi aman dibuka)
        Route::get('/administrasi/undangan', [AdministrasiController::class, 'undanganIndex'])->name('administrasi.undangan.index');
        Route::get('/administrasi/undangan/preview/{dosen}', [AdministrasiController::class, 'previewUndanganHtml'])->name('administrasi.undangan.preview');
        Route::get('/administrasi/undangan/pdf/{dosen}', [AdministrasiController::class, 'generateUndanganPdf'])->name('administrasi.undangan.pdf');
        Route::get('/administrasi/undangan/docx/{dosen}', [AdministrasiController::class, 'generateUndanganDocx'])->name('administrasi.undangan.docx');
        Route::get('/administrasi/undangan/excel/{dosen}', [AdministrasiController::class, 'generateUndanganExcel'])->name('administrasi.undangan.excel');
        Route::get('/administrasi/undangan/mass-excel', [AdministrasiController::class, 'generateUndanganMassExcel'])->name('administrasi.undangan.mass-excel');
        Route::get('/administrasi/undangan/rekap-dosen-penguji', [AdministrasiController::class, 'generateRekapDosenPengujiExcel'])->name('administrasi.undangan.rekap-dosen-penguji');
        Route::get('/administrasi/undangan/zip', [AdministrasiController::class, 'generateUndanganZip'])->name('administrasi.undangan.zip');
        Route::get('/administrasi/undangan/mass-docx', [AdministrasiController::class, 'generateUndanganMassDocxZip'])->name('administrasi.undangan.mass-docx');
        Route::get('/administrasi/rekap-pembimbing', [AdministrasiController::class, 'rekapPembimbingIndex'])->name('administrasi.rekap-pembimbing.index');
        Route::get('/administrasi/berita-acara', [AdministrasiController::class, 'beritaAcaraIndex'])->name('administrasi.berita-acara.index');
        Route::get('/administrasi/berita-acara/mass-pdf', [AdministrasiController::class, 'generateBeritaAcaraMassPdf'])->name('administrasi.berita-acara.mass-pdf');
        Route::get('/administrasi/berita-acara/mass-preview', [AdministrasiController::class, 'previewBeritaAcaraMassPdf'])->name('administrasi.berita-acara.mass-preview');
        Route::get('/administrasi/berita-acara/pdf/{sidang}', [AdministrasiController::class, 'generateBeritaAcaraPdf'])->name('administrasi.berita-acara.pdf');
        Route::get('/administrasi/berita-acara/preview/{sidang}', [AdministrasiController::class, 'previewBeritaAcaraPdf'])->name('administrasi.berita-acara.preview');
        Route::get('/administrasi/berita-acara/zip', [AdministrasiController::class, 'generateBeritaAcaraZip'])->name('administrasi.berita-acara.zip');
        Route::get('/administrasi/sk', [AdministrasiController::class, 'skIndex'])->name('administrasi.sk.index');
        Route::get('/administrasi/sk/export-pembimbing', [AdministrasiController::class, 'exportSkPembimbingExcel'])->name('administrasi.sk.export-pembimbing');
        Route::get('/administrasi/sk/export-penguji', [AdministrasiController::class, 'exportSkPengujiExcel'])->name('administrasi.sk.export-penguji');
        Route::get('/administrasi/audit-log', [AuditLogController::class, 'index'])->name('administrasi.audit-log.index');
    });

    // Pendaftaran & Kesediaan Dosen Management (Super Admin & Koordinator)
    Route::middleware('role:super_admin,koordinator')->group(function () {
        // Menu Pendaftaran & Verifikasi Pembayaran
        Route::get('/pendaftaran/sempro', [PendaftaranController::class, 'semproIndex'])->name('pendaftaran.sempro');
        Route::get('/pendaftaran/sempro/export', [PendaftaranController::class, 'exportExcelSempro'])->name('pendaftaran.sempro.export');
        Route::get('/pendaftaran/skripsi', [PendaftaranController::class, 'skripsiIndex'])->name('pendaftaran.skripsi');
        Route::get('/pendaftaran/skripsi/export', [PendaftaranController::class, 'exportExcelSkripsi'])->name('pendaftaran.skripsi.export');
        Route::post('/pendaftaran/bulk-verifikasi', [PendaftaranController::class, 'bulkVerifikasi'])->name('pendaftaran.bulk-verifikasi');
        Route::delete('/pendaftaran/bulk-destroy', [PendaftaranController::class, 'bulkDestroy'])->name('pendaftaran.bulk-destroy');
        Route::post('/pendaftaran/{sidang}/verifikasi', [PendaftaranController::class, 'verifikasi'])->name('pendaftaran.verifikasi');
        Route::delete('/pendaftaran/{sidang}', [PendaftaranController::class, 'destroy'])->name('pendaftaran.destroy');

        Route::get('/master/kesediaan-dosen', [KesediaanDosenController::class, 'index'])->name('master.kesediaan-dosen.index');
        Route::delete('/master/kesediaan-dosen/destroy-all', [KesediaanDosenController::class, 'destroyAll'])->name('master.kesediaan-dosen.destroy-all');
        Route::delete('/master/kesediaan-dosen/{kesediaanDosen}', [KesediaanDosenController::class, 'destroy'])->name('master.kesediaan-dosen.destroy');
        Route::post('/master/kesediaan-dosen/settings', [KesediaanDosenController::class, 'updateSettings'])->name('master.kesediaan-dosen.settings');
        Route::post('/master/kesediaan-dosen/toggle-access/{dosen}', [KesediaanDosenController::class, 'toggleAccess'])->name('master.kesediaan-dosen.toggle-access');
        Route::post('/master/kesediaan-dosen/destroy-group', [KesediaanDosenController::class, 'destroyGroup'])->name('master.kesediaan-dosen.destroy-group');
        Route::post('/master/kesediaan-dosen/import', [KesediaanDosenController::class, 'importExcel'])->name('master.kesediaan-dosen.import');

        Route::get('/administrasi/analitik', [AnalitikController::class, 'index'])->name('administrasi.analitik.index');

        // Jadwal Sidang Skripsi
        Route::get('/jadwal-ujian', [SkripsiController::class, 'jadwalIndex'])->name('jadwal-ujian.index');
        Route::get('/jadwal-ujian/export-bentrok', [SkripsiController::class, 'exportBentrok'])->name('jadwal-ujian.export-bentrok');
        Route::post('/jadwal/skripsi/{sidang}/jadwalkan', [SkripsiController::class, 'jadwalkan'])->name('jadwal.skripsi.jadwalkan');
        Route::patch('/jadwal/skripsi/{sidang}/reschedule', [SkripsiController::class, 'reschedule'])->name('jadwal.skripsi.reschedule');
        Route::post('/jadwal/skripsi/bulk-jadwalkan', [SkripsiController::class, 'bulkJadwalkan'])->name('jadwal.skripsi.bulk-jadwalkan');

        // Jadwal Sempro
        Route::get('/jadwal-sempro', [SemproController::class, 'jadwalIndex'])->name('jadwal-sempro.index');
        Route::post('/jadwal/sempro/{sidang}/jadwalkan', [SemproController::class, 'jadwalkan'])->name('jadwal.sempro.jadwalkan');
        Route::patch('/jadwal/sempro/{sidang}/reschedule', [SemproController::class, 'reschedule'])->name('jadwal.sempro.reschedule');
        Route::post('/jadwal/sempro/bulk-jadwalkan', [SemproController::class, 'bulkJadwalkan'])->name('jadwal.sempro.bulk-jadwalkan');
    });
    // Mahasiswa routes
    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/mahasiswa/dashboard', [MahasiswaController::class, 'dashboard'])->name('mahasiswa.dashboard');
        Route::get('/mahasiswa/pendaftaran', [MahasiswaController::class, 'semproIndex'])->name('mahasiswa.pendaftaran.index');
        Route::get('/mahasiswa/sempro', [MahasiswaController::class, 'semproIndex'])->name('mahasiswa.sempro.index');
        Route::get('/mahasiswa/skripsi', [MahasiswaController::class, 'skripsiIndex'])->name('mahasiswa.skripsi.index');
        Route::get('/mahasiswa/jadwal', [MahasiswaController::class, 'jadwalIndex'])->name('mahasiswa.jadwal.index');
        Route::get('/mahasiswa/jadwal/sempro', [MahasiswaController::class, 'jadwalSemproIndex'])->name('mahasiswa.jadwal.sempro');
        Route::get('/mahasiswa/jadwal/skripsi', [MahasiswaController::class, 'jadwalSkripsiIndex'])->name('mahasiswa.jadwal.skripsi');
        Route::post('/mahasiswa/daftar', [MahasiswaController::class, 'storeRegistration'])->name('mahasiswa.daftar.store');
        Route::post('/mahasiswa/sidang/{sidang}/update-bukti', [MahasiswaController::class, 'updateBukti'])->name('mahasiswa.sidang.update-bukti');
    });
    // Dosen routes
    Route::middleware(['role:dosen', 'menu.permission'])->group(function () {
        Route::get('/dosen/dashboard', [DosenPortalController::class, 'dashboard'])->name('dosen.dashboard');
        Route::get('/dosen/jadwal/sempro', [DosenPortalController::class, 'sempro'])->name('dosen.jadwal.sempro');
        Route::get('/dosen/jadwal/skripsi', [DosenPortalController::class, 'skripsi'])->name('dosen.jadwal.skripsi');
        Route::get('/dosen/kalender', [DosenPortalController::class, 'kalender'])->name('dosen.kalender');
        Route::get('/dosen/profil', fn() => redirect()->route('profile.edit'))->name('dosen.profil');
        Route::post('/dosen/kesediaan', [DosenPortalController::class, 'storeKesediaan'])->name('dosen.kesediaan.store');
        Route::delete('/dosen/kesediaan/{id}', [DosenPortalController::class, 'destroyKesediaan'])->name('dosen.kesediaan.destroy');
    });

    // Super Admin menu management
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/kelola-menu', [MenuController::class, 'index'])->name('admin.menus.index');
        Route::post('/kelola-menu', [MenuController::class, 'store'])->name('admin.menus.store');
        Route::put('/kelola-menu/{menu}', [MenuController::class, 'update'])->name('admin.menus.update');
        Route::delete('/kelola-menu/{menu}', [MenuController::class, 'destroy'])->name('admin.menus.destroy');
        Route::post('/kelola-menu/user/{user}', [MenuController::class, 'assignUserMenus'])->name('admin.menus.assign');
        Route::post('/kelola-menu/role/{role}', [MenuController::class, 'assignRoleMenus'])->name('admin.menus.assign-role');
    });
});

