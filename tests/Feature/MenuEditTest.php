<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Menu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MenuEditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default menus are seeded
        $menuController = new \App\Http\Controllers\MenuController();
        $menuController->ensureDefaultMenusExist();
    }

    public function test_super_admin_can_edit_menu(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );

        $menu = Menu::first();
        $this->assertNotNull($menu);

        $response = $this->actingAs($admin)->put(route('admin.menus.update', $menu), [
            'name' => 'Updated Menu Name',
            'route' => $menu->route,
            'icon' => $menu->icon,
            'role_default' => $menu->role_default,
            'sort_order' => $menu->sort_order,
            'is_active' => $menu->is_active ? 'on' : null,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertStatus(302); // Redirects to admin.menus.index on success if not JSON
        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'name' => 'Updated Menu Name',
        ]);
    }

    public function test_super_admin_can_edit_menu_json(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );

        $menu = Menu::first();
        $this->assertNotNull($menu);

        $response = $this->actingAs($admin)->putJson(route('admin.menus.update', $menu), [
            'name' => 'Updated Menu Name JSON',
            'route' => $menu->route,
            'icon' => $menu->icon,
            'role_default' => $menu->role_default,
            'sort_order' => $menu->sort_order,
            'is_active' => 'on',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('menus', [
            'id' => $menu->id,
            'name' => 'Updated Menu Name JSON',
        ]);
    }
}
