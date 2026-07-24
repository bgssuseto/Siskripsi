<?php

use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$penjadwalanMenu = Menu::where('name', 'Penjadwalan')->first();

if (!$penjadwalanMenu) {
    $penjadwalanMenu = Menu::create([
        'name' => 'Penjadwalan',
        'icon' => '📅',
        'route' => null,
        'order' => 20,
    ]);
}

$kesediaanMenu = Menu::updateOrCreate(
    ['route' => 'master.kesediaan-dosen.index'],
    [
        'name' => 'Kesediaan Dosen',
        'icon' => '📝',
        'parent_id' => $penjadwalanMenu->id,
        'order' => 15,
    ]
);

// Attach to Super Admin role
$superAdminMenuIds = Menu::pluck('id')->toArray();
$superAdminRole = User::ROLE_SUPER_ADMIN;

DB::table('role_menu')->where('role', $superAdminRole)->delete();
foreach ($superAdminMenuIds as $mId) {
    DB::table('role_menu')->insert([
        'role' => $superAdminRole,
        'menu_id' => $mId,
    ]);
}

echo "Kesediaan Dosen menu created and assigned to Super Admin!\n";
