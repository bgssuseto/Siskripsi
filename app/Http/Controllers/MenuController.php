<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MenuController extends Controller
{
    /**
     * Display Menu Management dashboard for Super Admin
     */
    public function index(Request $request): View
    {
        // Seed default system menus if table is empty
        $this->ensureDefaultMenusExist();

        $menus = Menu::orderBy('sort_order')->orderBy('id')->get();
        $users = User::with('menus')->orderBy('name')->get();

        $selectedUserId = $request->get('user_id');
        $selectedUser = $selectedUserId ? User::with('menus')->find($selectedUserId) : null;

        return view('admin.menus.index', compact('menus', 'users', 'selectedUser'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'route'        => ['nullable', 'string', 'max:255'],
            'icon'         => ['nullable', 'string', 'max:255'],
            'role_default' => ['nullable', 'string', 'in:mahasiswa,super_admin,koordinator,dosen,all'],
            'sort_order'   => ['nullable', 'integer'],
        ]);

        $menu = Menu::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu sistem berhasil ditambahkan!',
                'menu' => $menu
            ]);
        }

        return redirect()->route('admin.menus.index')->with('success', 'Menu sistem berhasil ditambahkan!');
    }

    /**
     * Update an existing menu
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'route'        => ['nullable', 'string', 'max:255'],
            'role_default' => ['nullable', 'string', 'in:mahasiswa,super_admin,koordinator,dosen,all'],
            'sort_order'   => ['nullable', 'integer'],
        ]);

        if ($request->has('icon')) {
            $validated['icon'] = $request->input('icon');
        }

        $validated['is_active'] = $request->has('is_active');

        $menu->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu berhasil diperbarui!',
                'menu' => $menu
            ]);
        }

        return redirect()->route('admin.menus.index')->with('success', 'Menu berhasil diperbarui!');
    }

    /**
     * Delete a menu
     */
    public function destroy(Request $request, Menu $menu)
    {
        $menu->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Menu berhasil dihapus!'
            ]);
        }

        return redirect()->route('admin.menus.index')->with('success', 'Menu berhasil dihapus!');
    }

    /**
     * Assign custom additional menus to a specific user
     */
    public function assignUserMenus(Request $request, User $user)
    {
        $menuIds = $request->input('menu_ids', []);
        
        $user->menus()->sync($menuIds);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Akses menu kustom untuk user {$user->name} berhasil diperbarui!"
            ]);
        }

        return redirect()->route('admin.menus.index', ['user_id' => $user->id])
            ->with('success', "Akses menu kustom untuk user {$user->name} berhasil diperbarui!");
    }

    /**
     * Assign dynamic menus to a specific role (e.g. dosen)
     */
    public function assignRoleMenus(Request $request, string $role)
    {
        $validated = $request->validate([
            'menu_ids' => ['array'],
            'menu_ids.*' => ['exists:menus,id'],
        ]);

        $menuIds = $request->input('menu_ids', []);

        // Delete existing menus for this role
        \Illuminate\Support\Facades\DB::table('role_menu')->where('role', $role)->delete();

        // Insert new ones
        $insertData = array_map(function ($menuId) use ($role) {
            return [
                'role' => $role,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $menuIds);

        if (!empty($insertData)) {
            \Illuminate\Support\Facades\DB::table('role_menu')->insert($insertData);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Hak akses menu untuk role {$role} berhasil diperbarui!"
            ]);
        }

        return redirect()->route('admin.menus.index')
            ->with('success', "Hak akses menu untuk role {$role} berhasil diperbarui!");
    }

    /**
     * Seed default system menus if menus table is empty
     */
    public function ensureDefaultMenusExist(): void
    {
        $defaultMenus = [
            [
                'name'         => 'Dashboard Mahasiswa',
                'route'        => 'mahasiswa.dashboard',
                'icon'         => 'home',
                'role_default' => 'mahasiswa',
                'sort_order'   => 1,
            ],
            [
                'name'         => 'Daftar Sempro',
                'route'        => 'mahasiswa.sempro.index',
                'icon'         => 'document',
                'role_default' => 'mahasiswa',
                'sort_order'   => 2,
            ],
            [
                'name'         => 'Daftar Skripsi',
                'route'        => 'mahasiswa.skripsi.index',
                'icon'         => 'academic',
                'role_default' => 'mahasiswa',
                'sort_order'   => 3,
            ],
            [
                'name'         => 'Administrasi Berita Acara',
                'route'        => 'administrasi.berita-acara.index',
                'icon'         => 'clipboard',
                'role_default' => 'super_admin',
                'sort_order'   => 5,
            ],
            [
                'name'         => 'Administrasi Undangan',
                'route'        => 'administrasi.undangan.index',
                'icon'         => 'mail',
                'role_default' => 'super_admin',
                'sort_order'   => 6,
            ],
            [
                'name'         => 'Administrasi SK',
                'route'        => 'administrasi.sk.index',
                'icon'         => 'badge',
                'role_default' => 'super_admin',
                'sort_order'   => 7,
            ],
            [
                'name'         => 'Manajemen User',
                'route'        => 'users.index',
                'icon'         => 'users',
                'role_default' => 'super_admin',
                'sort_order'   => 8,
            ],
            [
                'name'         => 'Data Master Dosen',
                'route'        => 'master.dosen.index',
                'icon'         => 'academic',
                'role_default' => 'super_admin',
                'sort_order'   => 9,
            ],
            [
                'name'         => 'Data Master Ruang',
                'route'        => 'master.ruang.index',
                'icon'         => 'building',
                'role_default' => 'super_admin',
                'sort_order'   => 10,
            ],
            [
                'name'         => 'Data Master Periode',
                'route'        => 'master.periode.index',
                'icon'         => 'clock',
                'role_default' => 'super_admin',
                'sort_order'   => 11,
            ],
            [
                'name'         => 'Kesediaan Dosen',
                'route'        => 'master.kesediaan-dosen.index',
                'icon'         => 'clipboard',
                'role_default' => 'super_admin',
                'sort_order'   => 12,
            ],
            [
                'name'         => 'Manajemen Menu',
                'route'        => 'admin.menus.index',
                'icon'         => 'cog',
                'role_default' => 'super_admin',
                'sort_order'   => 13,
            ],
            [
                'name'         => 'Backup & Restore',
                'route'        => 'backup.index',
                'icon'         => 'database',
                'role_default' => 'super_admin',
                'sort_order'   => 14,
            ],
            // ── Dosen menus ──
            [
                'name'         => 'Dashboard',
                'route'        => 'dosen.dashboard',
                'icon'         => 'home',
                'role_default' => 'dosen',
                'sort_order'   => 20,
            ],
            [
                'name'         => 'Kalender',
                'route'        => 'dosen.kalender',
                'icon'         => 'clock',
                'role_default' => 'dosen',
                'sort_order'   => 24,
            ]
        ];

        // Clean up old dosen.profil menu if exists
        $oldDosenProfil = Menu::where('route', 'dosen.profil')->first();
        if ($oldDosenProfil) {
            \Illuminate\Support\Facades\DB::table('role_menu')->where('menu_id', $oldDosenProfil->id)->delete();
            \Illuminate\Support\Facades\DB::table('user_menu')->where('menu_id', $oldDosenProfil->id)->delete();
            $oldDosenProfil->delete();
        }

        // Seed top-level menus first
        foreach ($defaultMenus as $menu) {
            Menu::firstOrCreate(['route' => $menu['route']], $menu);
        }

        // Group 'Manajemen User' and 'Manajemen Menu' under a single 'Manajemen' parent
        $manajemenParent = Menu::firstOrCreate(
            ['name' => 'Manajemen', 'role_default' => 'super_admin'],
            [
                'route'        => null,
                'icon'         => 'cog',
                'role_default' => 'super_admin',
                'sort_order'   => 8,
            ]
        );
        $manajemenMenuItem = Menu::where('route', 'admin.menus.index')->first();
        if ($manajemenMenuItem) {
            $manajemenMenuItem->update(['parent_id' => $manajemenParent->id, 'sort_order' => 1]);
        }
        $manajemenUserItem = Menu::where('route', 'users.index')->first();
        if ($manajemenUserItem) {
            $manajemenUserItem->update(['parent_id' => $manajemenParent->id, 'sort_order' => 2]);
        }

        // Remove any parent menu named 'Jadwal Sidang' for role_default = 'dosen' to prevent duplicate/incorrect menu
        $oldJadwalSidang = Menu::where('name', 'Jadwal Sidang')
            ->whereNull('parent_id')
            ->where('role_default', 'dosen')
            ->first();
        if ($oldJadwalSidang) {
            \Illuminate\Support\Facades\DB::table('role_menu')->where('menu_id', $oldJadwalSidang->id)->delete();
            \Illuminate\Support\Facades\DB::table('user_menu')->where('menu_id', $oldJadwalSidang->id)->delete();
            $oldJadwalSidang->delete();
        }

        // Remove the stale 'sidang.index' menu entirely — that route doesn't exist
        // anywhere in routes/web.php, so this entry always rendered as a dead '#' link.
        $staleJadwalMenu = Menu::where('route', 'sidang.index')->first();
        if ($staleJadwalMenu) {
            \Illuminate\Support\Facades\DB::table('role_menu')->where('menu_id', $staleJadwalMenu->id)->delete();
            \Illuminate\Support\Facades\DB::table('user_menu')->where('menu_id', $staleJadwalMenu->id)->delete();
            $staleJadwalMenu->delete();
        }

        // Seed Jadwal (parent) for Dosen
        $jadwalMenu = Menu::firstOrCreate(
            ['name' => 'Jadwal', 'role_default' => 'dosen'],
            [
                'route'        => null,
                'icon'         => 'calendar',
                'role_default' => 'dosen',
                'sort_order'   => 21,
            ]
        );

        // Seed children of Jadwal
        $semproMenu = Menu::firstOrCreate(
            ['route' => 'dosen.jadwal.sempro'],
            [
                'name'         => 'Seminar Proposal',
                'parent_id'    => $jadwalMenu->id,
                'icon'         => 'document',
                'role_default' => 'dosen',
                'sort_order'   => 22,
            ]
        );
        $semproMenu->update(['parent_id' => $jadwalMenu->id]);

        $skripsiMenu = Menu::firstOrCreate(
            ['route' => 'dosen.jadwal.skripsi'],
            [
                'name'         => 'Sidang Skripsi',
                'parent_id'    => $jadwalMenu->id,
                'icon'         => 'academic',
                'role_default' => 'dosen',
                'sort_order'   => 23,
            ]
        );
        $skripsiMenu->update(['parent_id' => $jadwalMenu->id]);

        // Seed default role_menu mappings for dosen
        $dosenMenuRoutes = [
            'dosen.dashboard',
            'dosen.kalender',
            'dosen.profil',
            'dosen.jadwal.sempro',
            'dosen.jadwal.skripsi'
        ];

        $dosenMenus = Menu::whereIn('route', $dosenMenuRoutes)
            ->orWhere(function($q) {
                $q->where('name', 'Jadwal')->where('role_default', 'dosen');
            })
            ->get();

        foreach ($dosenMenus as $dm) {
            \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                ['role' => 'dosen', 'menu_id' => $dm->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // ── Seed Mahasiswa Menus (Pendaftaran & Penjadwalan with submenus) ──
        $mhsPendaftaranParent = Menu::firstOrCreate(
            ['name' => 'Pendaftaran', 'role_default' => 'mahasiswa'],
            [
                'route'        => null,
                'icon'         => 'document',
                'role_default' => 'mahasiswa',
                'sort_order'   => 2,
            ]
        );

        $mhsSemproDaftar = Menu::firstOrCreate(
            ['route' => 'mahasiswa.sempro.index'],
            [
                'name'         => 'Daftar Sempro',
                'parent_id'    => $mhsPendaftaranParent->id,
                'icon'         => 'document',
                'role_default' => 'mahasiswa',
                'sort_order'   => 3,
            ]
        );
        $mhsSemproDaftar->update(['parent_id' => $mhsPendaftaranParent->id]);

        $mhsSkripsiDaftar = Menu::firstOrCreate(
            ['route' => 'mahasiswa.skripsi.index'],
            [
                'name'         => 'Daftar Skripsi',
                'parent_id'    => $mhsPendaftaranParent->id,
                'icon'         => 'academic',
                'role_default' => 'mahasiswa',
                'sort_order'   => 4,
            ]
        );
        $mhsSkripsiDaftar->update(['parent_id' => $mhsPendaftaranParent->id]);

        $mhsPenjadwalanParent = Menu::firstOrCreate(
            ['name' => 'Penjadwalan', 'role_default' => 'mahasiswa'],
            [
                'route'        => null,
                'icon'         => 'calendar',
                'role_default' => 'mahasiswa',
                'sort_order'   => 5,
            ]
        );

        $mhsJadwalSempro = Menu::firstOrCreate(
            ['route' => 'mahasiswa.jadwal.sempro'],
            [
                'name'         => 'Jadwal Sempro',
                'parent_id'    => $mhsPenjadwalanParent->id,
                'icon'         => 'calendar',
                'role_default' => 'mahasiswa',
                'sort_order'   => 6,
            ]
        );
        $mhsJadwalSempro->update(['parent_id' => $mhsPenjadwalanParent->id]);

        $mhsJadwalSkripsi = Menu::firstOrCreate(
            ['route' => 'mahasiswa.jadwal.skripsi'],
            [
                'name'         => 'Jadwal Skripsi',
                'parent_id'    => $mhsPenjadwalanParent->id,
                'icon'         => 'calendar',
                'role_default' => 'mahasiswa',
                'sort_order'   => 7,
            ]
        );
        $mhsJadwalSkripsi->update(['parent_id' => $mhsPenjadwalanParent->id]);

        // Seed default role_menu mappings for mahasiswa
        $mhsMenuRoutes = [
            'mahasiswa.dashboard',
            'mahasiswa.sempro.index',
            'mahasiswa.skripsi.index',
            'mahasiswa.jadwal.sempro',
            'mahasiswa.jadwal.skripsi'
        ];

        $mhsMenus = Menu::whereIn('route', $mhsMenuRoutes)
            ->orWhere(function($q) {
                $q->whereIn('name', ['Pendaftaran', 'Penjadwalan'])->where('role_default', 'mahasiswa');
            })
            ->get();

        foreach ($mhsMenus as $mm) {
            \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                ['role' => 'mahasiswa', 'menu_id' => $mm->id],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // ── Seed Koordinator Menus (Dashboard, Data, Penjadwalan, Pendaftaran, with submenus) ──
        Menu::firstOrCreate(
            ['route' => 'dashboard'],
            ['name' => 'Dashboard', 'icon' => 'home', 'role_default' => 'koordinator', 'sort_order' => 30]
        );

        $koordDataParent = Menu::firstOrCreate(
            ['name' => 'Data', 'role_default' => 'koordinator'],
            ['route' => null, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 31]
        );
        $koordDataSkripsi = Menu::firstOrCreate(
            ['route' => 'master.skripsi.index'],
            ['name' => 'Data Skripsi', 'parent_id' => $koordDataParent->id, 'icon' => 'academic', 'role_default' => 'koordinator', 'sort_order' => 32]
        );
        $koordDataSkripsi->update(['parent_id' => $koordDataParent->id]);
        $koordDataSempro = Menu::firstOrCreate(
            ['route' => 'master.sempro.index'],
            ['name' => 'Data Sempro', 'parent_id' => $koordDataParent->id, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 33]
        );
        $koordDataSempro->update(['parent_id' => $koordDataParent->id]);

        $koordPenjadwalanParent = Menu::firstOrCreate(
            ['name' => 'Penjadwalan', 'role_default' => 'koordinator'],
            ['route' => null, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 34]
        );
        $koordJadwalSkripsi = Menu::firstOrCreate(
            ['route' => 'jadwal-ujian.index'],
            ['name' => 'Jadwal Sidang Skripsi', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 35]
        );
        $koordJadwalSkripsi->update(['parent_id' => $koordPenjadwalanParent->id]);
        $koordJadwalSempro = Menu::firstOrCreate(
            ['route' => 'jadwal-sempro.index'],
            ['name' => 'Jadwal Sempro', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 36]
        );
        $koordJadwalSempro->update(['parent_id' => $koordPenjadwalanParent->id]);
        $koordKesediaan = Menu::firstOrCreate(
            ['route' => 'master.kesediaan-dosen.index'],
            ['name' => 'Kesediaan Dosen', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'clipboard', 'role_default' => 'koordinator', 'sort_order' => 37]
        );
        $koordKesediaan->update(['parent_id' => $koordPenjadwalanParent->id]);
        $koordAutoPlot = Menu::firstOrCreate(
            ['route' => 'jadwal.auto-plot.index'],
            ['name' => 'Asisten Plotting Otomatis', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 38]
        );
        $koordAutoPlot->update(['parent_id' => $koordPenjadwalanParent->id]);

        $koordPendaftaranParent = Menu::firstOrCreate(
            ['name' => 'Pendaftaran', 'role_default' => 'koordinator'],
            ['route' => null, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 39]
        );
        $koordVerifSkripsi = Menu::firstOrCreate(
            ['route' => 'pendaftaran.skripsi'],
            ['name' => 'Verifikasi Skripsi', 'parent_id' => $koordPendaftaranParent->id, 'icon' => 'academic', 'role_default' => 'koordinator', 'sort_order' => 40]
        );
        $koordVerifSkripsi->update(['parent_id' => $koordPendaftaranParent->id]);
        $koordVerifSempro = Menu::firstOrCreate(
            ['route' => 'pendaftaran.sempro'],
            ['name' => 'Verifikasi Sempro', 'parent_id' => $koordPendaftaranParent->id, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 41]
        );
        $koordVerifSempro->update(['parent_id' => $koordPendaftaranParent->id]);

        // ── Extra pages made manageable via Manajemen Menu, but NOT part of the
        //    koordinator default seed below — super_admin grants these explicitly
        //    via "Atur Akses Role Koordinator" when needed. ──
        Menu::firstOrCreate(
            ['route' => 'administrasi.rekap-pembimbing.index'],
            ['name' => 'Rekap Pembimbing', 'icon' => 'clipboard', 'role_default' => 'super_admin', 'sort_order' => 50]
        );
        Menu::firstOrCreate(
            ['route' => 'administrasi.analitik.index'],
            ['name' => 'Dashboard Analitik', 'icon' => 'chart', 'role_default' => 'super_admin', 'sort_order' => 51]
        );
        Menu::firstOrCreate(
            ['route' => 'administrasi.audit-log.index'],
            ['name' => 'Riwayat Aktivitas', 'icon' => 'clock', 'role_default' => 'super_admin', 'sort_order' => 52]
        );
        Menu::firstOrCreate(
            ['route' => 'master.dosen-penguji-rule.index'],
            ['name' => 'Rule Komposisi Penguji', 'icon' => 'cog', 'role_default' => 'super_admin', 'sort_order' => 53]
        );

        // Seed default role_menu mappings for koordinator — ONLY the core operational
        // pages above (Dashboard/Data/Penjadwalan/Pendaftaran). This runs ONCE ONLY
        // (guarded by the exists() check below): earlier this used to unconditionally
        // DELETE + reinsert koordinator's role_menu rows on every single page load of
        // Manajemen Menu, silently wiping out any manual access super_admin granted
        // via "Atur Akses Role Koordinator" moments earlier. Guarding it lets manual
        // customization persist across page loads like every other role already does.
        if (!\Illuminate\Support\Facades\DB::table('role_menu')->where('role', 'koordinator')->exists()) {
            $koordinatorDefaultRoutes = [
                'dashboard',
                'master.skripsi.index',
                'master.sempro.index',
                'jadwal-ujian.index',
                'jadwal-sempro.index',
                'master.kesediaan-dosen.index',
                'jadwal.auto-plot.index',
                'pendaftaran.skripsi',
                'pendaftaran.sempro',
            ];

            $koordinatorMenus = Menu::whereIn('route', $koordinatorDefaultRoutes)
                ->orWhere(function ($q) {
                    $q->whereIn('name', ['Data', 'Penjadwalan', 'Pendaftaran'])->where('role_default', 'koordinator');
                })
                ->get();

            foreach ($koordinatorMenus as $km) {
                \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                    ['role' => 'koordinator', 'menu_id' => $km->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
