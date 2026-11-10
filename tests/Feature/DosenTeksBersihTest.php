<?php

namespace Tests\Feature;

use App\Models\Dosen;
use App\Models\User;
use App\Support\TeksBersih;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DosenTeksBersihTest extends TestCase
{
    use DatabaseTransactions;

    public function test_nomor_wa_hasil_salin_dari_whatsapp_dibersihkan(): void
    {
        // Nomor yang disalin dari kontak WhatsApp: tanda arah teks + strip non-ASCII + NBSP.
        $this->assertSame('+6281234567890', TeksBersih::nomorHp("\u{202A}+62\u{00A0}812\u{2011}3456\u{2011}7890\u{202C}"));
        $this->assertSame('081234567890', TeksBersih::nomorHp(' 0812-3456-7890 '));
        $this->assertNull(TeksBersih::nomorHp("\u{200B} "));
        $this->assertSame("Dr. Ma'ruf, M.Kom", TeksBersih::teks("Dr.\u{00A0}Ma\u{2019}ruf,  M.Kom\u{FEFF}"));
    }

    public function test_update_dosen_menyimpan_data_yang_sudah_bersih(): void
    {
        (new \App\Http\Controllers\MenuController())->ensureDefaultMenusExist();
        $admin = User::create(['name' => 'Admin Teks', 'email' => 'teks' . uniqid() . '@test.id',
            'password' => bcrypt('x'), 'role' => User::ROLE_SUPER_ADMIN]);
        $dosen = Dosen::create(['nama_dosen' => 'Dosen Teks, M.Kom', 'nidn' => 'TKS' . substr(uniqid(), -7)]);

        $this->actingAs($admin)->putJson(route('master.dosen.update', $dosen), [
            'nidn'               => $dosen->nidn,
            'nama_dosen'         => "Dosen\u{00A0}Teks, M.Kom",
            'kepakaran'          => "Data\u{200B} Mining",
            'status_kepegawaian' => 'aktif',
            'no_wa'              => "\u{202A}0812\u{2011}1111\u{2011}2222\u{202C}",
        ])->assertOk()->assertJsonPath('success', true);

        $dosen->refresh();
        $this->assertSame('081211112222', $dosen->no_wa);
        $this->assertSame('Dosen Teks, M.Kom', $dosen->nama_dosen);
        $this->assertSame('Data Mining', $dosen->kepakaran);
    }
}
