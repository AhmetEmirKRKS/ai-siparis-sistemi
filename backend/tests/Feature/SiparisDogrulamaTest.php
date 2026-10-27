<?php

namespace Tests\Feature;

use App\Models\Konusma;
use App\Models\Siparis;
use App\Models\Urun;
use App\Services\SiparisServisi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SiparisDogrulamaTest extends TestCase
{
    use RefreshDatabase;

    public function test_siparis_kaydedilir_ve_fiyat_veritabanindan_alinir(): void
    {
        $urun = $this->urunOlustur();
        $konusma = Konusma::create();

        $bilgiler = $this->siparisBilgileri($urun);
        // dışarıdan fiyat gelse bile kullanılmamalı
        $bilgiler['kalemler'][0]['birim_fiyat'] = 1;

        $siparis = app(SiparisServisi::class)->olustur($konusma->id, $bilgiler);

        $this->assertSame('499.80', $siparis->toplam);
        $this->assertSame('249.90', $siparis->kalemler->first()->birim_fiyat);
        $this->assertSame('05321234567', $siparis->telefon);
        $this->assertSame('beklemede', $siparis->durum);
        $this->assertSame(8, $urun->fresh()->stok);
    }

    public function test_stok_yetersizse_siparis_olusmaz(): void
    {
        $urun = $this->urunOlustur(['stok' => 1]);

        $this->hataBekle('stok', $this->siparisBilgileri($urun));

        $this->assertSame(0, Siparis::count());
        $this->assertSame(1, $urun->fresh()->stok);
    }

    public function test_ayni_urunun_toplam_adedi_stogu_asarsa_siparis_olusmaz(): void
    {
        $urun = $this->urunOlustur(['stok' => 10]);

        $bilgiler = $this->siparisBilgileri($urun, [
            'kalemler' => [
                ['urun_id' => $urun->id, 'adet' => 6, 'secenekler' => ['beden' => 'S']],
                ['urun_id' => $urun->id, 'adet' => 6, 'secenekler' => ['beden' => 'M']],
            ],
        ]);

        $this->hataBekle('stok', $bilgiler);
        $this->assertSame(0, Siparis::count());
    }

    public function test_gecersiz_telefon_kabul_edilmez(): void
    {
        $urun = $this->urunOlustur();

        $this->hataBekle('telefon', $this->siparisBilgileri($urun, ['telefon' => '12345']));
        $this->assertSame(0, Siparis::count());
    }

    public function test_gecersiz_secenek_kabul_edilmez(): void
    {
        $urun = $this->urunOlustur();

        $bilgiler = $this->siparisBilgileri($urun, [
            'kalemler' => [['urun_id' => $urun->id, 'adet' => 1, 'secenekler' => ['beden' => 'XXL']]],
        ]);

        $this->hataBekle('secenekler', $bilgiler);
    }

    public function test_olmayan_urun_kabul_edilmez(): void
    {
        $urun = $this->urunOlustur();

        $bilgiler = $this->siparisBilgileri($urun, [
            'kalemler' => [['urun_id' => 999, 'adet' => 1, 'secenekler' => []]],
        ]);

        $this->hataBekle('kalemler.0.urun_id', $bilgiler);
    }

    public function test_ayni_konusma_icin_ikinci_siparis_olusmaz(): void
    {
        $urun = $this->urunOlustur();
        $konusma = Konusma::create();
        $servis = app(SiparisServisi::class);

        $ilk = $servis->olustur($konusma->id, $this->siparisBilgileri($urun));
        $ikinci = $servis->olustur($konusma->id, $this->siparisBilgileri($urun));

        $this->assertSame($ilk->id, $ikinci->id);
        $this->assertSame(1, Siparis::count());
        $this->assertSame(8, $urun->fresh()->stok);
    }

    private function urunOlustur(array $degerler = []): Urun
    {
        return Urun::create(array_merge([
            'ad' => 'Basic Tişört',
            'fiyat' => 249.90,
            'stok' => 10,
            'secenekler' => ['beden' => ['S', 'M']],
        ], $degerler));
    }

    private function siparisBilgileri(Urun $urun, array $degisiklikler = []): array
    {
        return array_merge([
            'kalemler' => [
                ['urun_id' => $urun->id, 'adet' => 2, 'secenekler' => ['beden' => 'M']],
            ],
            'ad_soyad' => 'Ahmet Yılmaz',
            'telefon' => '0532 123 45 67',
            'adres' => 'Moda Cad. No:5 Kadıköy',
        ], $degisiklikler);
    }

    private function hataBekle(string $alan, array $bilgiler): void
    {
        try {
            app(SiparisServisi::class)->olustur(Konusma::create()->id, $bilgiler);
            $this->fail('Sipariş oluşmamalıydı.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($alan, $e->errors());
        }
    }
}