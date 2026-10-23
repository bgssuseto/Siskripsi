<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Daftarkan menu "Undangan Menguji" di portal dosen (undangan berjalan & arsip
 * per semester) supaya langsung muncul di sidebar dosen setelah deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $menuId = DB::table('menus')->where('route', 'dosen.undangan.index')->value('id')
            ?? DB::table('menus')->insertGetId([
                'name' => 'Undangan Menguji', 'route' => 'dosen.undangan.index', 'icon' => 'mail',
                'role_default' => 'dosen', 'is_active' => true, 'sort_order' => 23,
                'created_at' => $now, 'updated_at' => $now,
            ]);

        DB::table('role_menu')->updateOrInsert(
            ['role' => 'dosen', 'menu_id' => $menuId],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        $ids = DB::table('menus')->where('route', 'dosen.undangan.index')->pluck('id');
        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('user_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }
};
