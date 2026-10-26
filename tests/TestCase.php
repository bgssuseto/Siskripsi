<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /** Database tes sudah dicek & dimigrasi pada proses ini. */
    private static bool $databaseSiap = false;

    /**
     * Dijalankan setelah aplikasi dibuat tetapi SEBELUM trait seperti RefreshDatabase
     * menyentuh database — jadi pengaman di bawah berlaku untuk semua tes.
     */
    protected function setUpTraits()
    {
        if (!self::$databaseSiap) {
            $this->siapkanDatabaseTes();
            self::$databaseSiap = true;
        }

        return parent::setUpTraits();
    }

    /**
     * Tes hanya boleh berjalan di database yang namanya berakhiran "_test" (lihat
     * phpunit.xml) — dulu tes memakai database kerja dan RefreshDatabase
     * mengosongkannya. Database tes dibuat & dimigrasi otomatis bila belum ada.
     */
    private function siapkanDatabaseTes(): void
    {
        $koneksi = config('database.default');
        $c = config("database.connections.{$koneksi}");
        $nama = (string) ($c['database'] ?? '');

        if (($c['driver'] ?? '') !== 'sqlite' && !str_ends_with($nama, '_test')) {
            fwrite(STDERR, "\nTes dihentikan: database '{$nama}' bukan database tes (harus berakhiran _test). Periksa DB_DATABASE di phpunit.xml.\n");
            exit(1);
        }

        if (in_array($c['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            $pdo = new \PDO("mysql:host={$c['host']};port={$c['port']}", $c['username'], $c['password']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$nama}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            DB::purge($koneksi);
        }

        Artisan::call('migrate', ['--force' => true]);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // Kembalikan user & menu bawaan di database tes setelah tiap kelas tes.
        try {
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            // Abaikan.
        }
    }
}
