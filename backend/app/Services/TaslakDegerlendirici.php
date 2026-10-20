<?php

namespace App\Services;

use App\Models\Urun;

class TaslakDegerlendirici
{
    public function __construct(private UrunEslestirici $eslestirici)
    {
    }

    public function degerlendir(?array $taslak): array
    {
        $taslak = $taslak ?? [];
        $sorular = [];
        $kalemler = [];

        if (empty($taslak['kalemler'])) {
            $sorular[] = 'Hangi ürünü sipariş etmek istersiniz?';
        }

        foreach ($taslak['kalemler'] ?? [] as $kalem) {
            [$sonuc, $kalemSorulari] = $this->kalemiKontrolEt($kalem);

            $sorular = array_merge($sorular, $kalemSorulari);

            if ($sonuc) {
                $kalemler[] = $sonuc;
            }
        }

        $telefon = Telefon::duzenle($taslak['telefon'] ?? null);

        if (empty($taslak['ad_soyad'])) {
            $sorular[] = 'Adınızı ve soyadınızı yazar mısınız?';
        }

        if (empty($taslak['telefon'])) {
            $sorular[] = 'Telefon numaranızı yazar mısınız?';
        } elseif ($telefon === null) {
            $sorular[] = 'Telefon numaranız geçerli görünmüyor. 05XX XXX XX XX şeklinde yazar mısınız?';
        }

        if (empty($taslak['adres'])) {
            $sorular[] = 'Teslimat adresinizi yazar mısınız?';
        }

        return [
            'sorular' => $sorular,
            'kalemler' => $kalemler,
            'toplam' => round(array_sum(array_column($kalemler, 'tutar')), 2),
            'ad_soyad' => $taslak['ad_soyad'] ?? null,
            'telefon' => $telefon,
            'adres' => $taslak['adres'] ?? null,
        ];
    }

    private function kalemiKontrolEt(array $kalem): array
    {
        $urunAdi = $kalem['urun'] ?? '';
        $adaylar = $this->eslestirici->bul($urunAdi);

        if ($adaylar->isEmpty()) {
            $tumUrunler = Urun::pluck('ad')->join(', ');
            return [null, ["\"{$urunAdi}\" adında bir ürün bulamadık. Ürünlerimiz: {$tumUrunler}."]];
        }

        if ($adaylar->count() > 1) {
            $secenekler = $adaylar->pluck('ad')->join(', ');
            return [null, ["\"{$urunAdi}\" için birden fazla ürün var: {$secenekler}. Hangisini istiyorsunuz?"]];
        }

        $urun = $adaylar->first();
        $sorular = [];
        $secilenler = [];

        foreach ($urun->secenekler ?? [] as $secenekAdi => $gecerliDegerler) {
            $verilen = collect($kalem['secenekler'] ?? [])
                ->first(fn ($s) => UrunEslestirici::sadelestir($s['ad']) === UrunEslestirici::sadelestir($secenekAdi));

            if (! $verilen) {
                $sorular[] = "{$urun->ad} için {$secenekAdi} seçer misiniz? (" . implode(', ', $gecerliDegerler) . ')';
                continue;
            }

            $gecerli = collect($gecerliDegerler)
                ->first(fn ($d) => UrunEslestirici::sadelestir($d) === UrunEslestirici::sadelestir($verilen['deger']));

            if (! $gecerli) {
                $sorular[] = "{$urun->ad} için \"{$verilen['deger']}\" {$secenekAdi} seçeneği yok. Seçenekler: " . implode(', ', $gecerliDegerler) . '.';
                continue;
            }

            $secilenler[$secenekAdi] = $gecerli;
        }

        $adet = $kalem['adet'] ?? null;

        if ($urun->stok < 1) {
            $sorular[] = "{$urun->ad} şu an stokta yok. Başka bir ürün seçmek ister misiniz?";
        } elseif (! $adet) {
            $sorular[] = "Kaç adet {$urun->ad} istiyorsunuz?";
        } elseif ($adet > $urun->stok) {
            $sorular[] = "{$urun->ad} için stokta sadece {$urun->stok} adet var. Adedi değiştirmek ister misiniz?";
        }

        if ($sorular) {
            return [null, $sorular];
        }

        $birimFiyat = (float) $urun->fiyat;

        return [[
            'urun_id' => $urun->id,
            'urun_adi' => $urun->ad,
            'secenekler' => $secilenler,
            'adet' => $adet,
            'birim_fiyat' => $birimFiyat,
            'tutar' => round($birimFiyat * $adet, 2),
        ], []];
    }

    public function cevapYaz(array $sonuc): string
    {
        if ($sonuc['sorular']) {
            return "Siparişinizi tamamlamak için birkaç bilgiye ihtiyacım var:\n- " . implode("\n- ", $sonuc['sorular']);
        }

        $satirlar = ['Siparişinizin özeti:'];

        foreach ($sonuc['kalemler'] as $kalem) {
            $secenek = '';
            if ($kalem['secenekler']) {
                $secenek = ' (' . collect($kalem['secenekler'])->map(fn ($deger, $ad) => "{$ad}: {$deger}")->join(', ') . ')';
            }

            $satirlar[] = "- {$kalem['adet']} x {$kalem['urun_adi']}{$secenek} = " . $this->tl($kalem['tutar']);
        }

        $satirlar[] = 'Toplam: ' . $this->tl($sonuc['toplam']);
        $satirlar[] = "Teslimat: {$sonuc['ad_soyad']}, {$sonuc['telefon']}, {$sonuc['adres']}";
        $satirlar[] = '';
        $satirlar[] = 'Bilgiler doğruysa "Siparişi Onayla" butonuna basabilirsiniz. Değiştirmek istediğiniz bir şey varsa yazmanız yeterli.';

        return implode("\n", $satirlar);
    }

    private function tl(float $tutar): string
    {
        return number_format($tutar, 2, ',', '.') . ' TL';
    }
}