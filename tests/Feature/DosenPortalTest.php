<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Menu;
use App\Models\Dosen;
use App\Models\Sidang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DosenPortalTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure default menus are seeded
        $menuController = new \App\Http\Controllers\MenuController();
        $menuController->ensureDefaultMenusExist();

        $dosen = Dosen::firstOrCreate(['nidn' => '0012058501'], ['nama_dosen' => 'Arief Susanto, ST., M.Kom']);
        User::firstOrCreate(
            ['email' => 'arief@skripsi.ac.id'],
            [
                'name' => 'Arief Susanto, ST., M.Kom',
                'password' => bcrypt('password'),
                'role' => User::ROLE_DOSEN,
                'dosen_id' => $dosen->id
            ]
        );
    }

    public function test_dosen_user_can_access_authorized_pages(): void
    {
        // Get seeded Dosen user
        $dosenUser = User::where('email', 'arief@skripsi.ac.id')->first();
        $this->assertNotNull($dosenUser);

        // Access dashboard
        $response = $this->actingAs($dosenUser)->get(route('dosen.dashboard'));
        $response->assertStatus(200);

        // Access profil (redirects to unified profile page)
        $response = $this->actingAs($dosenUser)->get(route('dosen.profil'));
        $response->assertRedirect(route('profile.edit'));
    }

    public function test_dosen_access_without_role_menu_permission_returns_forbidden(): void
    {
        $dosenUser = User::where('email', 'arief@skripsi.ac.id')->first();
        $this->assertNotNull($dosenUser);

        // Remove a menu from role_menu (e.g., Kalender)
        $kalenderMenu = Menu::where('route', 'dosen.kalender')->first();
        $this->assertNotNull($kalenderMenu);
        $kalenderMenu->update(['role_default' => null]);

        DB::table('role_menu')
            ->where('role', 'dosen')
            ->where('menu_id', $kalenderMenu->id)
            ->delete();

        // Also delete from user_menu pivot to prevent custom user overrides
        DB::table('user_menu')
            ->where('user_id', $dosenUser->id)
            ->where('menu_id', $kalenderMenu->id)
            ->delete();

        // Accessing kalender should return 403
        $response = $this->actingAs($dosenUser)->get(route('dosen.kalender'));
        $response->assertStatus(403);
    }

    public function test_dosen_access_without_dashboard_permission_returns_forbidden(): void
    {
        $dosenUser = User::where('email', 'arief@skripsi.ac.id')->first();
        $this->assertNotNull($dosenUser);

        // Remove Dashboard menu from role_menu
        $dashboardMenu = Menu::where('route', 'dosen.dashboard')->first();
        $this->assertNotNull($dashboardMenu);
        $dashboardMenu->update(['role_default' => null]);

        DB::table('role_menu')
            ->where('role', 'dosen')
            ->where('menu_id', $dashboardMenu->id)
            ->delete();

        // Also delete from user_menu pivot
        DB::table('user_menu')
            ->where('user_id', $dosenUser->id)
            ->where('menu_id', $dashboardMenu->id)
            ->delete();

        // Accessing dosen.dashboard directly should return 403
        $response = $this->actingAs($dosenUser)->get(route('dosen.dashboard'));
        $response->assertStatus(403);

        // Accessing general dashboard route should redirect to dosen.dashboard
        $response = $this->actingAs($dosenUser)->get(route('dashboard'));
        $response->assertRedirect(route('dosen.dashboard'));
        
        // Following the redirect should result in 403
        $response = $this->actingAs($dosenUser)->get(route('dosen.dashboard'));
        $response->assertStatus(403);
    }

    public function test_deleting_dosen_user_reassigns_data_to_super_admin(): void
    {
        // 1. Create a Dosen user, a super admin user, and some kesediaan + sidang data
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );

        $dosen = Dosen::create(['nidn' => '123456789', 'nama_dosen' => 'Test Dosen']);
        $dosenUser = User::create([
            'name' => 'Test Dosen',
            'email' => 'testdosen@skripsi.ac.id',
            'password' => bcrypt('password'),
            'role' => User::ROLE_DOSEN,
            'dosen_id' => $dosen->id
        ]);

        // Create kesediaan_dosens record
        $kesediaan = \App\Models\KesediaanDosen::create([
            'dosen_id' => $dosen->id,
            'tanggal' => '2026-07-24',
            'jam_mulai' => '08:00',
            'jam_selesai' => '09:00',
            'keterangan' => 'Test Kesediaan'
        ]);

        // Create a sidang where this Dosen is ketua penguji
        $sidang = Sidang::create([
            'nim' => '202251999',
            'nama_mahasiswa' => 'Test Student',
            'judul_skripsi' => 'Test Skripsi Title',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'ketua_penguji_id' => $dosen->id,
            'jenis_tugas_akhir' => 'skripsi'
        ]);

        // 2. Delete the Dosen user as Super Admin
        $response = $this->actingAs($superAdmin)->delete(route('users.destroy', $dosenUser));
        $response->assertStatus(302); // Redirects back

        // 3. Verify user is deleted and Dosen is deleted
        $this->assertDatabaseMissing('users', ['id' => $dosenUser->id]);
        $this->assertDatabaseMissing('dosens', ['id' => $dosen->id]);

        // 4. Verify Super Administrator Dosen exists and is linked to Super Admin User
        $superAdminDosen = Dosen::where('nidn', '0000000000')->first();
        $this->assertNotNull($superAdminDosen);
        $superAdmin->refresh();
        $this->assertEquals($superAdminDosen->id, $superAdmin->dosen_id);

        // 5. Verify data is transferred to Super Administrator Dosen
        $this->assertDatabaseHas('kesediaan_dosens', [
            'id' => $kesediaan->id,
            'dosen_id' => $superAdminDosen->id
        ]);
        $this->assertDatabaseHas('sidangs', [
            'id' => $sidang->id,
            'dosen_pembimbing_utama_id' => $superAdminDosen->id,
            'ketua_penguji_id' => $superAdminDosen->id
        ]);
    }

    public function test_deleting_dosen_master_reassigns_data_to_super_admin(): void
    {
        // 1. Create a Dosen, a super admin user, and some kesediaan + sidang data
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@skripsi.ac.id'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => User::ROLE_SUPER_ADMIN]
        );

        $dosen = Dosen::create(['nidn' => '987654321', 'nama_dosen' => 'Test Master Dosen']);

        // Create kesediaan_dosens record
        $kesediaan = \App\Models\KesediaanDosen::create([
            'dosen_id' => $dosen->id,
            'tanggal' => '2026-07-24',
            'jam_mulai' => '10:00',
            'jam_selesai' => '11:00',
            'keterangan' => 'Test Master Kesediaan'
        ]);

        // Create a sidang where this Dosen is ketua penguji and pembimbing
        $sidang = Sidang::create([
            'nim' => '202251998',
            'nama_mahasiswa' => 'Test Student 2',
            'judul_skripsi' => 'Test Skripsi Title 2',
            'dosen_pembimbing_utama_id' => $dosen->id,
            'ketua_penguji_id' => $dosen->id,
            'jenis_tugas_akhir' => 'skripsi'
        ]);

        // 2. Delete the Dosen master record as Super Admin
        $response = $this->actingAs($superAdmin)->delete(route('master.dosen.destroy', $dosen));
        $response->assertStatus(302); // Redirects back

        // 3. Verify Dosen is deleted
        $this->assertDatabaseMissing('dosens', ['id' => $dosen->id]);

        // 4. Verify Super Administrator Dosen exists and is linked to Super Admin User
        $superAdminDosen = Dosen::where('nidn', '0000000000')->first();
        $this->assertNotNull($superAdminDosen);
        $superAdmin->refresh();
        $this->assertEquals($superAdminDosen->id, $superAdmin->dosen_id);

        // 5. Verify data is transferred to Super Administrator Dosen
        $this->assertDatabaseHas('kesediaan_dosens', [
            'id' => $kesediaan->id,
            'dosen_id' => $superAdminDosen->id
        ]);
        $this->assertDatabaseHas('sidangs', [
            'id' => $sidang->id,
            'dosen_pembimbing_utama_id' => $superAdminDosen->id,
            'ketua_penguji_id' => $superAdminDosen->id
        ]);
    }
}
