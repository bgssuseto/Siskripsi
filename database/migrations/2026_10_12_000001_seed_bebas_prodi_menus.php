<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Daftarkan menu "Bebas Prodi" ke tabel menus supaya langsung muncul di sidebar
 * mahasiswa setelah deploy (tanpa menunggu super admin membuka Kelola Menu,
 * yang menjalankan MenuController::ensureDefaultMenusExist()).
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $mhsMenuId = DB::table('menus')->where('route', 'mahasiswa.bebas-prodi.index')->value('id')
            ?? DB::table('menus')->insertGetId([
                'name' => 'Bebas Prodi', 'route' => 'mahasiswa.bebas-prodi.index', 'icon' => 'badge',
                'role_default' => 'mahasiswa', 'is_active' => true, 'sort_order' => 8,
                'created_at' => $now, 'updated_at' => $now,
            ]);

        DB::table('role_menu')->updateOrInsert(
            ['role' => 'mahasiswa', 'menu_id' => $mhsMenuId],
            ['created_at' => $now, 'updated_at' => $now]
        );

        // Menu admin untuk koordinator — tidak otomatis diberikan; super admin
        // bisa mengaktifkannya lewat "Atur Akses Role Koordinator".
        if (!DB::table('menus')->where('route', 'bebas-prodi.index')->exists()) {
            DB::table('menus')->insert([
                'name' => 'Bebas Prodi', 'route' => 'bebas-prodi.index', 'icon' => 'badge',
                'role_default' => 'koordinator', 'is_active' => true, 'sort_order' => 45,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('menus')->whereIn('route', ['mahasiswa.bebas-prodi.index', 'bebas-prodi.index'])->pluck('id');
        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('user_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }
};
