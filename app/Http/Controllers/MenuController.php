<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class MenuController extends Controller
{
    /** Role yang hak akses menunya diatur di sini (super_admin selalu akses penuh). */
    public const ROLE_DIATUR = [User::ROLE_KOORDINATOR, User::ROLE_DOSEN, User::ROLE_MAHASISWA];

    /** Kelompok menu (kolom role_default) — menentukan bagian sidebar tempat menu tampil. */
    public const KELOMPOK = ['koordinator', 'dosen', 'mahasiswa', 'super_admin', 'all'];

    /**
     * Halaman Manajemen Menu & Hak Akses: struktur menu (pohon induk/sub-menu),
     * hak akses per role, dan akses khusus per user — dikelola tanpa reload.
     */
    public function index(Request $request): View
    {
        $this->ensureDefaultMenusExist();

        $menus = Menu::orderBy('sort_order')->orderBy('id')->get();

        $roleMenu = collect(self::ROLE_DIATUR)->mapWithKeys(fn ($role) => [
            $role => DB::table('role_menu')->where('role', $role)->pluck('menu_id')->map(fn ($id) => (int) $id)->values(),
        ]);

        $statistik = [
            'total'       => $menus->count(),
            'aktif'       => $menus->where('is_active', true)->count(),
            'userKhusus'  => DB::table('user_menu')->distinct()->count('user_id'),
            'jumlahUser'  => User::selectRaw('role, count(*) as n')->groupBy('role')->pluck('n', 'role'),
        ];

        // Nama route GET tanpa parameter — saran isian "Route" saat menambah/mengubah menu.
        $daftarRoute = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->getName() && in_array('GET', $r->methods(), true) && !str_contains($r->uri(), '{'))
            ->map(fn ($r) => $r->getName())
            ->reject(fn ($n) => str_starts_with($n, 'ignition.') || str_starts_with($n, 'sanctum.') || str_starts_with($n, 'verifikasi.') || $n === 'storage.local')
            ->unique()->sort()->values();

        $daftarIkon = ['home', 'document', 'document-add', 'academic', 'calendar', 'clipboard', 'clipboard-list', 'users', 'user',
            'user-circle', 'cog', 'chart', 'mail', 'badge-check', 'shield', 'clock', 'check-circle', 'info', 'upload', 'download',
            'printer', 'search', 'filter', 'link', 'chat', 'building', 'map-pin', 'credit-card', 'sparkles', 'inbox', 'book', 'lock',
            'bell', 'eye', 'folder', 'list', 'flag', 'database', 'presentation', 'lightning', 'key', 'tag'];

        return view('admin.menus.index', compact('menus', 'roleMenu', 'statistik', 'daftarRoute', 'daftarIkon'));
    }

    /** Data menu untuk halaman (JSON ringkas, dipakai juga setelah simpan). */
    public static function menuJson(Menu $m): array
    {
        return [
            'id'           => $m->id,
            'hid'          => $m->hash_id,
            'name'         => $m->name,
            'route'        => $m->route,
            'icon'         => $m->icon,
            'role_default' => $m->role_default ?? 'all',
            'parent_id'    => $m->parent_id,
            'is_active'    => (bool) $m->is_active,
            'sort_order'   => (int) $m->sort_order,
        ];
    }

    private function aturanValidasi(?Menu $menu = null): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'route'        => ['nullable', 'string', 'max:255'],
            'icon'         => ['nullable', 'string', 'max:255'],
            'role_default' => ['nullable', 'string', 'in:' . implode(',', self::KELOMPOK)],
            'parent_id'    => ['nullable', 'integer', 'exists:menus,id', function ($attr, $value, $fail) use ($menu) {
                if (!$value) return;
                $induk = Menu::find($value);
                if ($induk?->parent_id) {
                    $fail('Induk harus menu utama (bukan sub-menu).');
                }
                if ($menu && ((int) $value === $menu->id || Menu::where('parent_id', $menu->id)->exists())) {
                    $fail($menu->id === (int) $value ? 'Menu tidak bisa menjadi induk dirinya sendiri.' : 'Menu yang memiliki sub-menu tidak bisa dijadikan sub-menu.');
                }
            }],
            'sort_order'   => ['nullable', 'integer'],
        ];
    }

    private function jawab(Request $request, string $pesan, array $extra = [], int $status = 200)
    {
        User::clearAccessibleMenusCache();

        if ($request->expectsJson()) {
            return response()->json(['success' => $status < 400, 'message' => $pesan] + $extra, $status);
        }

        return redirect()->route('admin.menus.index')->with($status < 400 ? 'success' : 'error', $pesan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->aturanValidasi());
        $validated['role_default'] ??= 'all';
        $validated['is_active'] = $request->boolean('is_active', true);

        // Urutan otomatis: taruh paling bawah di antara saudaranya.
        if (!isset($validated['sort_order'])) {
            $validated['sort_order'] = (int) Menu::where('parent_id', $validated['parent_id'] ?? null)
                ->where('role_default', $validated['role_default'])->max('sort_order') + 1;
        }

        $menu = Menu::create($validated);

        ActivityLogger::log('created', $menu, "Menambahkan menu sistem baru: {$menu->name}" . ($menu->route ? " (route: {$menu->route})" : '') . '.');

        return $this->jawab($request, 'Menu sistem berhasil ditambahkan!', ['menu' => self::menuJson($menu)]);
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate($this->aturanValidasi($menu));

        if (!$request->has('icon')) {
            unset($validated['icon']);
        }
        if (!$request->has('parent_id')) {
            unset($validated['parent_id']);
        }
        if (!isset($validated['sort_order'])) {
            unset($validated['sort_order']);
        }
        $validated['is_active'] = $request->boolean('is_active');

        $before = $menu->only(['name', 'route', 'role_default', 'parent_id', 'sort_order', 'is_active']);
        $menu->update($validated);

        ActivityLogger::log('updated', $menu, "Memperbarui menu sistem: {$menu->name}.",
            ['before' => $before, 'after' => $menu->only(['name', 'route', 'role_default', 'parent_id', 'sort_order', 'is_active'])]);

        return $this->jawab($request, 'Menu berhasil diperbarui!', ['menu' => self::menuJson($menu->fresh())]);
    }

    /** Aktifkan / nonaktifkan menu (menu nonaktif hilang dari sidebar semua role). */
    public function toggle(Request $request, Menu $menu)
    {
        $menu->update(['is_active' => !$menu->is_active]);

        ActivityLogger::log('updated', $menu, ($menu->is_active ? 'Mengaktifkan' : 'Menonaktifkan') . " menu: {$menu->name}.");

        return $this->jawab($request, "Menu {$menu->name} " . ($menu->is_active ? 'diaktifkan.' : 'dinonaktifkan.'), ['menu' => self::menuJson($menu)]);
    }

    /** Simpan urutan baru menu-menu bersaudara (daftar id sesuai urutan tampil). */
    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:menus,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach (array_values($validated['ids']) as $urutan => $id) {
                Menu::whereKey($id)->update(['sort_order' => $urutan + 1]);
            }
        });

        ActivityLogger::log('updated', null, 'Mengubah urutan ' . count($validated['ids']) . ' menu.');

        return $this->jawab($request, 'Urutan menu disimpan.');
    }

    public function destroy(Request $request, Menu $menu)
    {
        if (Menu::where('parent_id', $menu->id)->exists()) {
            return $this->jawab($request, "Menu {$menu->name} masih memiliki sub-menu. Pindahkan atau hapus sub-menunya terlebih dahulu.", [], 422);
        }

        $menuName = $menu->name;
        DB::table('role_menu')->where('menu_id', $menu->id)->delete();
        DB::table('user_menu')->where('menu_id', $menu->id)->delete();
        $menu->delete();

        ActivityLogger::log('deleted', $menu, "Menghapus menu sistem: {$menuName}.");

        return $this->jawab($request, 'Menu berhasil dihapus!');
    }

    /**
     * Cari user untuk tab "Akses Khusus User" (dimuat sebagian, tidak semua
     * user sekaligus). Menyertakan menu dari role dan menu khusus user tsb.
     */
    public function users(Request $request)
    {
        $q    = trim((string) $request->get('q'));
        $role = $request->get('role');

        $users = User::query()
            ->with(['menus:id', 'additionalRoles'])
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when(in_array($role, [User::ROLE_SUPER_ADMIN, ...self::ROLE_DIATUR], true), fn ($w) => $w->where('role', $role))
            ->when($request->boolean('khusus'), fn ($w) => $w->whereHas('menus'))
            ->orderBy('name')
            ->paginate(15);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $u) => [
                'hid'       => $u->hash_id,
                'name'      => $u->name,
                'email'     => $u->email,
                'role'      => $u->role,
                'roles'     => $u->getRoleNames(),
                'role_label'=> $u->role_label,
                'custom'    => $u->menus->pluck('id')->map(fn ($id) => (int) $id)->values(),
            ])->values(),
            'total'     => $users->total(),
            'halaman'   => $users->currentPage(),
            'terakhir'  => $users->lastPage(),
        ]);
    }

    /** Akses menu tambahan khusus untuk satu user (di luar menu dari role-nya). */
    public function assignUserMenus(Request $request, User $user)
    {
        $request->validate([
            'menu_ids'   => ['array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
        ]);

        // Simpan hanya menu tambahan — menu yang sudah didapat dari role tidak perlu diduplikasi.
        $dariRole = DB::table('role_menu')->whereIn('role', $user->getRoleNames())->pluck('menu_id')->map(fn ($id) => (int) $id)->all();
        $menuIds = collect($request->input('menu_ids', []))->map(fn ($id) => (int) $id)->diff($dariRole)->unique()->values()->all();

        $user->menus()->sync($menuIds);

        ActivityLogger::log('updated', $user, "Mengatur akses menu khusus untuk user {$user->name} ({$user->email}) — " . count($menuIds) . ' menu tambahan.');

        return $this->jawab($request, "Akses khusus untuk {$user->name} berhasil disimpan.", ['custom' => $menuIds]);
    }

    /** Hak akses menu default untuk satu role (koordinator / dosen / mahasiswa). */
    public function assignRoleMenus(Request $request, string $role)
    {
        abort_unless(in_array($role, self::ROLE_DIATUR, true), 404);

        $request->validate([
            'menu_ids'   => ['array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
        ]);

        $menuIds = collect($request->input('menu_ids', []))->map(fn ($id) => (int) $id)->unique()->values();

        DB::transaction(function () use ($role, $menuIds) {
            DB::table('role_menu')->where('role', $role)->delete();
            DB::table('role_menu')->insert($menuIds->map(fn ($id) => [
                'role' => $role, 'menu_id' => $id, 'created_at' => now(), 'updated_at' => now(),
            ])->all());
        });

        ActivityLogger::log('updated', null, "Mengatur hak akses menu untuk role {$role} — " . $menuIds->count() . ' menu diberikan.');

        return $this->jawab($request, 'Hak akses role ' . ucfirst($role) . ' berhasil disimpan.', ['menu_ids' => $menuIds]);
    }

    /**
     * Seed default system menus if menus table is empty
     */
    public function ensureDefaultMenusExist(): void
    {
        // Menu default yang baru dibuat pada pemanggilan ini tetap diberi hak akses
        // default role-nya; menu lama tidak disentuh (akses yang dicabut tidak kembali).
        $idTerakhir = (int) Menu::max('id');
        $baru = fn ($m) => $m->id > $idTerakhir;
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
        if ($manajemenMenuItem && !$manajemenMenuItem->parent_id) {
            $manajemenMenuItem->update(['parent_id' => $manajemenParent->id, 'sort_order' => 1]);
        }
        $manajemenUserItem = Menu::where('route', 'users.index')->first();
        if ($manajemenUserItem && !$manajemenUserItem->parent_id) {
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
        if (!$semproMenu->parent_id) {
            $semproMenu->update(['parent_id' => $jadwalMenu->id]);
        }

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
        if (!$skripsiMenu->parent_id) {
            $skripsiMenu->update(['parent_id' => $jadwalMenu->id]);
        }

        // Seed Riwayat Menguji (top-level, bukan submenu Jadwal — ini rekap/
        // laporan histori, bukan penjadwalan yang sedang berjalan).
        Menu::firstOrCreate(
            ['route' => 'dosen.riwayat'],
            [
                'name'         => 'Riwayat Menguji',
                'parent_id'    => null,
                'icon'         => 'document',
                'role_default' => 'dosen',
                'sort_order'   => 24,
            ]
        );

        // Seed menu SK (unduh SK Pembimbing & Penguji per periode).
        Menu::firstOrCreate(
            ['route' => 'dosen.sk.index'],
            [
                'name'         => 'SK',
                'parent_id'    => null,
                'icon'         => 'badge',
                'role_default' => 'dosen',
                'sort_order'   => 25,
            ]
        );

        // Undangan menguji milik dosen (berjalan & arsip per semester).
        Menu::firstOrCreate(
            ['route' => 'dosen.undangan.index'],
            ['name' => 'Undangan Menguji', 'parent_id' => null, 'icon' => 'mail', 'role_default' => 'dosen', 'sort_order' => 23]
        );

        // Seed default role_menu mappings for dosen
        $dosenMenuRoutes = [
            'dosen.undangan.index',
            'dosen.dashboard',
            'dosen.kalender',
            'dosen.profil',
            'dosen.jadwal.sempro',
            'dosen.jadwal.skripsi',
            'dosen.riwayat',
            'dosen.sk.index',
        ];

        $dosenMenus = Menu::whereIn('route', $dosenMenuRoutes)
            ->orWhere(function($q) {
                $q->where('name', 'Jadwal')->where('role_default', 'dosen');
            })
            ->get();

        // Hanya diisi sekali (role belum punya hak akses sama sekali) — supaya
        // menu yang sengaja dicabut lewat Manajemen Menu tidak kembali lagi.
        $roleKosong = !\Illuminate\Support\Facades\DB::table('role_menu')->where('role', 'dosen')->exists();
        {
            foreach ($dosenMenus->filter(fn ($x) => $roleKosong || $baru($x)) as $dm) {
                \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                    ['role' => 'dosen', 'menu_id' => $dm->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
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
        if (!$mhsSemproDaftar->parent_id) {
            $mhsSemproDaftar->update(['parent_id' => $mhsPendaftaranParent->id]);
        }

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
        if (!$mhsSkripsiDaftar->parent_id) {
            $mhsSkripsiDaftar->update(['parent_id' => $mhsPendaftaranParent->id]);
        }

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
        if (!$mhsJadwalSempro->parent_id) {
            $mhsJadwalSempro->update(['parent_id' => $mhsPenjadwalanParent->id]);
        }

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
        if (!$mhsJadwalSkripsi->parent_id) {
            $mhsJadwalSkripsi->update(['parent_id' => $mhsPenjadwalanParent->id]);
        }

        Menu::firstOrCreate(
            ['route' => 'mahasiswa.bebas-prodi.index'],
            ['name' => 'Bebas Prodi', 'icon' => 'badge', 'role_default' => 'mahasiswa', 'sort_order' => 8]
        );

        // Halaman admin Bebas Prodi — koordinator baru bisa membuka bila
        // diberi akses lewat "Atur Akses Role Koordinator" (tidak di-default).
        Menu::firstOrCreate(
            ['route' => 'bebas-prodi.index'],
            ['name' => 'Bebas Prodi', 'icon' => 'badge', 'role_default' => 'koordinator', 'sort_order' => 45]
        );

        // Seed default role_menu mappings for mahasiswa
        $mhsMenuRoutes = [
            'mahasiswa.dashboard',
            'mahasiswa.sempro.index',
            'mahasiswa.skripsi.index',
            'mahasiswa.jadwal.sempro',
            'mahasiswa.jadwal.skripsi',
            'mahasiswa.bebas-prodi.index',
        ];

        $mhsMenus = Menu::whereIn('route', $mhsMenuRoutes)
            ->orWhere(function($q) {
                $q->whereIn('name', ['Pendaftaran', 'Penjadwalan'])->where('role_default', 'mahasiswa');
            })
            ->get();

        // Hanya diisi sekali (role belum punya hak akses sama sekali) — supaya
        // menu yang sengaja dicabut lewat Manajemen Menu tidak kembali lagi.
        $roleKosong = !\Illuminate\Support\Facades\DB::table('role_menu')->where('role', 'mahasiswa')->exists();
        {
            foreach ($mhsMenus->filter(fn ($x) => $roleKosong || $baru($x)) as $mm) {
                \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                    ['role' => 'mahasiswa', 'menu_id' => $mm->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
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
        if (!$koordDataSkripsi->parent_id) {
            $koordDataSkripsi->update(['parent_id' => $koordDataParent->id]);
        }
        $koordDataSempro = Menu::firstOrCreate(
            ['route' => 'master.sempro.index'],
            ['name' => 'Data Sempro', 'parent_id' => $koordDataParent->id, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 33]
        );
        if (!$koordDataSempro->parent_id) {
            $koordDataSempro->update(['parent_id' => $koordDataParent->id]);
        }

        $koordPenjadwalanParent = Menu::firstOrCreate(
            ['name' => 'Penjadwalan', 'role_default' => 'koordinator'],
            ['route' => null, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 34]
        );
        $koordJadwalSkripsi = Menu::firstOrCreate(
            ['route' => 'jadwal-ujian.index'],
            ['name' => 'Jadwal Sidang Skripsi', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 35]
        );
        if (!$koordJadwalSkripsi->parent_id) {
            $koordJadwalSkripsi->update(['parent_id' => $koordPenjadwalanParent->id]);
        }
        $koordJadwalSempro = Menu::firstOrCreate(
            ['route' => 'jadwal-sempro.index'],
            ['name' => 'Jadwal Sempro', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 36]
        );
        if (!$koordJadwalSempro->parent_id) {
            $koordJadwalSempro->update(['parent_id' => $koordPenjadwalanParent->id]);
        }
        $koordKesediaan = Menu::firstOrCreate(
            ['route' => 'master.kesediaan-dosen.index'],
            ['name' => 'Kesediaan Dosen', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'clipboard', 'role_default' => 'koordinator', 'sort_order' => 37]
        );
        if (!$koordKesediaan->parent_id) {
            $koordKesediaan->update(['parent_id' => $koordPenjadwalanParent->id]);
        }
        $koordAutoPlot = Menu::firstOrCreate(
            ['route' => 'jadwal.auto-plot.index'],
            ['name' => 'Asisten Plotting Otomatis', 'parent_id' => $koordPenjadwalanParent->id, 'icon' => 'calendar', 'role_default' => 'koordinator', 'sort_order' => 38]
        );
        if (!$koordAutoPlot->parent_id) {
            $koordAutoPlot->update(['parent_id' => $koordPenjadwalanParent->id]);
        }

        $koordPendaftaranParent = Menu::firstOrCreate(
            ['name' => 'Pendaftaran', 'role_default' => 'koordinator'],
            ['route' => null, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 39]
        );
        $koordVerifSkripsi = Menu::firstOrCreate(
            ['route' => 'pendaftaran.skripsi'],
            ['name' => 'Verifikasi Skripsi', 'parent_id' => $koordPendaftaranParent->id, 'icon' => 'academic', 'role_default' => 'koordinator', 'sort_order' => 40]
        );
        if (!$koordVerifSkripsi->parent_id) {
            $koordVerifSkripsi->update(['parent_id' => $koordPendaftaranParent->id]);
        }
        $koordVerifSempro = Menu::firstOrCreate(
            ['route' => 'pendaftaran.sempro'],
            ['name' => 'Verifikasi Sempro', 'parent_id' => $koordPendaftaranParent->id, 'icon' => 'document', 'role_default' => 'koordinator', 'sort_order' => 41]
        );
        if (!$koordVerifSempro->parent_id) {
            $koordVerifSempro->update(['parent_id' => $koordPendaftaranParent->id]);
        }

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
        $koordinatorKosong = !\Illuminate\Support\Facades\DB::table('role_menu')->where('role', 'koordinator')->exists();
        {
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

            foreach ($koordinatorMenus->filter(fn ($x) => $koordinatorKosong || $baru($x)) as $km) {
                \Illuminate\Support\Facades\DB::table('role_menu')->updateOrInsert(
                    ['role' => 'koordinator', 'menu_id' => $km->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
