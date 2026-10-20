<?php

namespace App\Services;

use App\Models\Urun;
use Illuminate\Support\Collection;

class UrunEslestirici
{
    public function bul(string $arama): Collection
    {
        $aranan = self::sadelestir($arama);

        if ($aranan === '') {
            return collect();
        }

        $urunler = Urun::all();

        $birebir = $urunler->first(fn (Urun $urun) => self::sadelestir($urun->ad) === $aranan);
        if ($birebir) {
            return collect([$birebir]);
        }

        $kelimeler = explode(' ', $aranan);

        return $urunler->filter(function (Urun $urun) use ($kelimeler) {
            $urunKelimeleri = explode(' ', self::sadelestir($urun->ad));

            foreach ($kelimeler as $kelime) {
                if (! $this->kelimeUyuyor($kelime, $urunKelimeleri)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    private function kelimeUyuyor(string $kelime, array $urunKelimeleri): bool
    {
        foreach ($urunKelimeleri as $urunKelimesi) {
            if (str_contains($urunKelimesi, $kelime)) {
                return true;
            }

            if (strlen($urunKelimesi) >= 4 && str_contains($kelime, $urunKelimesi)) {
                return true;
            }

            if (strlen($kelime) >= 5 && levenshtein($kelime, $urunKelimesi) <= 2) {
                return true;
            }
        }

        return false;
    }

    public static function sadelestir(string $metin): string
    {
        $metin = str_replace(
            ['Ç', 'Ğ', 'İ', 'I', 'Ö', 'Ş', 'Ü', 'ç', 'ğ', 'ı', 'ö', 'ş', 'ü'],
            ['c', 'g', 'i', 'i', 'o', 's', 'u', 'c', 'g', 'i', 'o', 's', 'u'],
            $metin
        );
        $metin = strtolower($metin);
        $metin = preg_replace('/[^a-z0-9 ]/', ' ', $metin);

        return trim(preg_replace('/\s+/', ' ', $metin));
    }
}
