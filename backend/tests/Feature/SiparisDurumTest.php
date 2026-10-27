<?php

namespace Tests\Feature;

use App\Models\Konusma;
use App\Models\Siparis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiparisDurumTest extends TestCase
{
    use RefreshDatabase;

    public function test_izin_verilen_gecisler(): void
    {
        $this->assertTrue((new Siparis(['durum' => 'beklemede']))->durumaGecebilirMi('hazirlaniyor'));
        $this->assertTrue((new Siparis(['durum' => 'beklemede']))->durumaGecebilirMi('iptal_edildi'));
        $this->assertTrue((new Siparis(['durum' => 'hazirlaniyor']))->durumaGecebilirMi('kargoda'));
        $this->assertTrue((new Siparis(['durum' => 'kargoda']))->durumaGecebilirMi('teslim_edildi'));
    }

    public function test_izin_verilmeyen_gecisler(): void
    {
        $this->assertFalse((new Siparis(['durum' => 'teslim_edildi']))->durumaGecebilirMi('hazirlaniyor'));
        $this->assertFalse((new Siparis(['durum' => 'beklemede']))->durumaGecebilirMi('teslim_edildi'));
        $this->assertFalse((new Siparis(['durum' => 'kargoda']))->durumaGecebilirMi('iptal_edildi'));
        $this->assertFalse((new Siparis(['durum' => 'iptal_edildi']))->durumaGecebilirMi('beklemede'));
    }

    public function test_admin_gecerli_durum_degisikligi_yapabilir(): void
    {
        $siparis = $this->siparisOlustur('beklemede');
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/admin/siparisler/' . $siparis->id . '/durum', ['durum' => 'hazirlaniyor'])
            ->assertOk();

        $this->assertSame('hazirlaniyor', $siparis->fresh()->durum);
    }

    public function test_teslim_edilmis_siparis_tekrar_hazirlaniyor_yapilamaz(): void
    {
        $siparis = $this->siparisOlustur('teslim_edildi');
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/admin/siparisler/' . $siparis->id . '/durum', ['durum' => 'hazirlaniyor'])
            ->assertStatus(422);

        $this->assertSame('teslim_edildi', $siparis->fresh()->durum);
    }

    public function test_giris_yapmadan_durum_degistirilemez(): void
    {
        $siparis = $this->siparisOlustur('beklemede');

        $this->patchJson('/api/admin/siparisler/' . $siparis->id . '/durum', ['durum' => 'hazirlaniyor'])
            ->assertStatus(401);

        $this->assertSame('beklemede', $siparis->fresh()->durum);
    }

    private function siparisOlustur(string $durum): Siparis
    {
        return Siparis::create([
            'siparis_no' => 'SIP-TEST0001',
            'konusma_id' => Konusma::create()->id,
            'ad_soyad' => 'Test Kullanıcı',
            'telefon' => '05321234567',
            'adres' => 'Test adresi',
            'toplam' => 100,
            'durum' => $durum,
        ]);
    }
}