<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Daftarkan menu "SK" di portal dosen (unduh SK Pembimbing & SK Penguji per
 * periode) supaya langsung muncul di sidebar dosen setelah deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $menuId = DB::table('menus')->where('route', 'dosen.sk.index')->value('id')
            ?? DB::table('menus')->insertGetId([
                'name' => 'SK', 'route' => 'dosen.sk.index', 'icon' => 'badge',
                'role_default' => 'dosen', 'is_active' => true, 'sort_order' => 25,
                'created_at' => $now, 'updated_at' => $now,
            ]);

        DB::table('role_menu')->updateOrInsert(
            ['role' => 'dosen', 'menu_id' => $menuId],
            ['created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        $ids = DB::table('menus')->where('route', 'dosen.sk.index')->pluck('id');
        DB::table('role_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('user_menu')->whereIn('menu_id', $ids)->delete();
        DB::table('menus')->whereIn('id', $ids)->delete();
    }
};
