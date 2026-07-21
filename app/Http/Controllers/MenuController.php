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
     * Store a new system menu
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'route'        => ['nullable', 'string', 'max:255'],
            'icon'         => ['nullable', 'string', 'max:255'],
            'role_default' => ['nullable', 'string', 'in:mahasiswa,super_admin,koordinator,dosen,all'],
            'sort_order'   => ['nullable', 'integer'],
        ]);

        Menu::create($validated);

        return redirect()->route('admin.menus.index')->with('success', 'Menu sistem berhasil ditambahkan!');
    }

    /**
     * Update an existing menu
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'route'        => ['nullable', 'string', 'max:255'],
            'icon'         => ['nullable', 'string', 'max:255'],
            'role_default' => ['nullable', 'string', 'in:mahasiswa,super_admin,koordinator,dosen,all'],
            'sort_order'   => ['nullable', 'integer'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->has('is_active');

        $menu->update($validated);

        return redirect()->route('admin.menus.index')->with('success', 'Menu berhasil diperbarui!');
    }

    /**
     * Delete a menu
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')->with('success', 'Menu berhasil dihapus!');
    }

    /**
     * Assign custom additional menus to a specific user
     */
    public function assignUserMenus(Request $request, User $user): RedirectResponse
    {
        $menuIds = $request->input('menu_ids', []);
        
        $user->menus()->sync($menuIds);

        return redirect()->route('admin.menus.index', ['user_id' => $user->id])
            ->with('success', "Akses menu kustom untuk user {$user->name} berhasil diperbarui!");
    }

    /**
     * Seed default system menus if menus table is empty
     */
    private function ensureDefaultMenusExist(): void
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
                'name'         => 'Jadwal Sidang',
                'route'        => 'sidang.index',
                'icon'         => 'calendar',
                'role_default' => 'all',
                'sort_order'   => 4,
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
                'name'         => 'Manajemen Menu',
                'route'        => 'admin.menus.index',
                'icon'         => 'cog',
                'role_default' => 'super_admin',
                'sort_order'   => 12,
            ],
        ];

        foreach ($defaultMenus as $menu) {
            Menu::firstOrCreate(['route' => $menu['route']], $menu);
        }
    }
}
