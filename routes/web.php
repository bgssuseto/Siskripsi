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

Route::get('/', function () {
    return redirect()->route('login');
});

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
        return view('dashboard');
    })->name('dashboard');

    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])->name('verification.notice');
    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])->middleware('signed')->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    // User Management (Super Admin & Koordinator)
    Route::middleware('role:super_admin,koordinator')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Data Master - Dosen
        Route::get('/master/dosen', [DosenController::class, 'index'])->name('master.dosen.index');
        Route::post('/master/dosen', [DosenController::class, 'store'])->name('master.dosen.store');
        Route::put('/master/dosen/{dosen}', [DosenController::class, 'update'])->name('master.dosen.update');
        Route::delete('/master/dosen/{dosen}', [DosenController::class, 'destroy'])->name('master.dosen.destroy');

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

        // Data Skripsi
        Route::get('/master/skripsi', [SkripsiController::class, 'index'])->name('master.skripsi.index');
        Route::post('/master/skripsi', [SkripsiController::class, 'store'])->name('master.skripsi.store');
        Route::put('/master/skripsi/{id}', [SkripsiController::class, 'update'])->name('master.skripsi.update');
        Route::delete('/master/skripsi/{id}', [SkripsiController::class, 'destroy'])->name('master.skripsi.destroy');
        Route::get('/master/skripsi/import', [SkripsiController::class, 'importForm'])->name('master.skripsi.import.form');
        Route::post('/master/skripsi/import', [SkripsiController::class, 'import'])->name('master.skripsi.import');

        // Data Sempro
        Route::get('/master/sempro', [SemproController::class, 'index'])->name('master.sempro.index');
        Route::post('/master/sempro', [SemproController::class, 'store'])->name('master.sempro.store');
        Route::put('/master/sempro/{id}', [SemproController::class, 'update'])->name('master.sempro.update');
        Route::delete('/master/sempro/{id}', [SemproController::class, 'destroy'])->name('master.sempro.destroy');
        Route::get('/master/sempro/import', [SemproController::class, 'importForm'])->name('master.sempro.import.form');
        Route::post('/master/sempro/import', [SemproController::class, 'import'])->name('master.sempro.import');

        // Jadwal Sidang Skripsi
        Route::get('/jadwal-ujian', [SkripsiController::class, 'jadwalIndex'])->name('jadwal-ujian.index');
        Route::post('/jadwal/skripsi/{sidang}/jadwalkan', [SkripsiController::class, 'jadwalkan'])->name('jadwal.skripsi.jadwalkan');

        // Jadwal Sempro
        Route::get('/jadwal-sempro', [SemproController::class, 'jadwalIndex'])->name('jadwal-sempro.index');
        Route::post('/jadwal/sempro/{sidang}/jadwalkan', [SemproController::class, 'jadwalkan'])->name('jadwal.sempro.jadwalkan');

        // Administrasi
        Route::get('/administrasi/undangan', [AdministrasiController::class, 'undanganIndex'])->name('administrasi.undangan.index');
        Route::get('/administrasi/undangan/pdf/{dosen}', [AdministrasiController::class, 'generateUndanganPdf'])->name('administrasi.undangan.pdf');
        Route::get('/administrasi/undangan/docx/{dosen}', [AdministrasiController::class, 'generateUndanganDocx'])->name('administrasi.undangan.docx');
        Route::get('/administrasi/undangan/excel/{dosen}', [AdministrasiController::class, 'generateUndanganExcel'])->name('administrasi.undangan.excel');
        Route::get('/administrasi/undangan/mass-excel', [AdministrasiController::class, 'generateUndanganMassExcel'])->name('administrasi.undangan.mass-excel');
        Route::get('/administrasi/undangan/zip', [AdministrasiController::class, 'generateUndanganZip'])->name('administrasi.undangan.zip');
        Route::get('/administrasi/berita-acara', [AdministrasiController::class, 'beritaAcaraIndex'])->name('administrasi.berita-acara.index');
        Route::get('/administrasi/berita-acara/mass-pdf', [AdministrasiController::class, 'generateBeritaAcaraMassPdf'])->name('administrasi.berita-acara.mass-pdf');
        Route::get('/administrasi/berita-acara/mass-preview', [AdministrasiController::class, 'previewBeritaAcaraMassPdf'])->name('administrasi.berita-acara.mass-preview');
        Route::get('/administrasi/berita-acara/pdf/{sidang}', [AdministrasiController::class, 'generateBeritaAcaraPdf'])->name('administrasi.berita-acara.pdf');
        Route::get('/administrasi/berita-acara/preview/{sidang}', [AdministrasiController::class, 'previewBeritaAcaraPdf'])->name('administrasi.berita-acara.preview');
        Route::get('/administrasi/berita-acara/zip', [AdministrasiController::class, 'generateBeritaAcaraZip'])->name('administrasi.berita-acara.zip');
        Route::get('/administrasi/sk', [AdministrasiController::class, 'skIndex'])->name('administrasi.sk.index');
    });
    // Mahasiswa routes
    Route::middleware('role:mahasiswa')->group(function () {
        Route::get('/mahasiswa/dashboard', [MahasiswaController::class, 'dashboard'])->name('mahasiswa.dashboard');
        Route::get('/mahasiswa/pendaftaran', [MahasiswaController::class, 'semproIndex'])->name('mahasiswa.pendaftaran.index');
        Route::get('/mahasiswa/sempro', [MahasiswaController::class, 'semproIndex'])->name('mahasiswa.sempro.index');
        Route::get('/mahasiswa/skripsi', [MahasiswaController::class, 'skripsiIndex'])->name('mahasiswa.skripsi.index');
        Route::get('/mahasiswa/jadwal', [MahasiswaController::class, 'jadwalIndex'])->name('mahasiswa.jadwal.index');
    });
    // Super Admin & Koordinator menu management
    Route::middleware('role:super_admin,koordinator')->group(function () {
        Route::get('/kelola-menu', [MenuController::class, 'index'])->name('admin.menus.index');
        Route::post('/kelola-menu', [MenuController::class, 'store'])->name('admin.menus.store');
        Route::put('/kelola-menu/{menu}', [MenuController::class, 'update'])->name('admin.menus.update');
        Route::delete('/kelola-menu/{menu}', [MenuController::class, 'destroy'])->name('admin.menus.destroy');
        Route::post('/kelola-menu/user/{user}', [MenuController::class, 'assignUserMenus'])->name('admin.menus.assign');
    });
});

