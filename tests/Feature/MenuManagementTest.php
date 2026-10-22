<?php

namespace Tests\Feature;

use App\Http\Controllers\MenuController;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MenuManagementTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        (new MenuController())->ensureDefaultMenusExist();

        $this->admin = User::create([
            'name' => 'Admin Menu', 'email' => 'admin-menu@example.test',
            'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_halaman_tampil_dengan_tab_hak_akses_struktur_dan_user(): void
    {
        $this->actingAs($this->admin)->get(route('admin.menus.index'))
            ->assertOk()
            ->assertSee('Hak Akses Role')
            ->assertSee('Struktur Menu')
            ->assertSee('Akses Khusus User');
    }

    public function test_akses_role_yang_dicabut_tidak_kembali_setelah_halaman_dibuka_lagi(): void
    {
        $jadwal = Menu::where('route', 'mahasiswa.jadwal.sempro')->firstOrFail();
        $sisa = DB::table('role_menu')->where('role', 'mahasiswa')->where('menu_id', '!=', $jadwal->id)->pluck('menu_id')->all();

        $this->actingAs($this->admin)->postJson(route('admin.menus.assign-role', 'mahasiswa'), ['menu_ids' => $sisa])
            ->assertOk()->assertJsonPath('success', true);

        $this->get(route('admin.menus.index'))->assertOk();

        $this->assertDatabaseMissing('role_menu', ['role' => 'mahasiswa', 'menu_id' => $jadwal->id]);
    }

    public function test_role_tidak_dikenal_ditolak(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.menus.assign-role', 'hacker'), ['menu_ids' => []])->assertNotFound();
    }

    public function test_toggle_urutan_dan_sub_menu(): void
    {
        $this->actingAs($this->admin);

        $induk = $this->postJson(route('admin.menus.store'), ['name' => 'Induk Uji', 'role_default' => 'koordinator'])
            ->assertOk()->json('menu');
        $a = $this->postJson(route('admin.menus.store'), ['name' => 'Anak A', 'route' => 'dashboard', 'role_default' => 'koordinator', 'parent_id' => $induk['id']])
            ->assertOk()->json('menu');
        $b = $this->postJson(route('admin.menus.store'), ['name' => 'Anak B', 'role_default' => 'koordinator', 'parent_id' => $induk['id']])
            ->assertOk()->json('menu');

        // Sub-menu tidak bisa menjadi induk.
        $this->postJson(route('admin.menus.store'), ['name' => 'Cucu', 'role_default' => 'koordinator', 'parent_id' => $a['id']])
            ->assertStatus(422)->assertJsonValidationErrors('parent_id');

        $this->postJson(route('admin.menus.reorder'), ['ids' => [$b['id'], $a['id']]])->assertOk();
        $this->assertSame(1, Menu::find($b['id'])->sort_order);
        $this->assertSame(2, Menu::find($a['id'])->sort_order);

        $this->postJson(route('admin.menus.toggle', Menu::find($a['id'])))->assertOk()->assertJsonPath('menu.is_active', false);

        // Induk yang masih punya sub-menu tidak bisa dihapus.
        $this->deleteJson(route('admin.menus.destroy', Menu::find($induk['id'])))->assertStatus(422);
    }

    public function test_akses_khusus_user_hanya_menyimpan_menu_tambahan(): void
    {
        $mhs = User::create(['name' => 'Mhs Menu', 'email' => 'mhs-menu@example.test', 'password' => bcrypt('x'), 'role' => User::ROLE_MAHASISWA]);
        $dariRole = (int) DB::table('role_menu')->where('role', 'mahasiswa')->value('menu_id');
        $tambahan = Menu::where('route', 'administrasi.analitik.index')->firstOrFail()->id;

        $this->actingAs($this->admin)->postJson(route('admin.menus.assign', $mhs), ['menu_ids' => [$dariRole, $tambahan]])
            ->assertOk()->assertJsonPath('custom', [$tambahan]);

        $this->getJson(route('admin.menus.users', ['q' => 'Mhs Menu']))
            ->assertOk()->assertJsonPath('data.0.custom', [$tambahan]);
    }
}
