<?php

namespace App\Services;

use App\Models\Konusma;
use App\Models\Siparis;
use App\Models\Urun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SiparisServisi
{
    public function olustur(string $konusmaId, array $bilgiler): Siparis
    {
        $veri = Validator::make($bilgiler, [
            'ad_soyad' => ['required', 'string', 'max:255'],
            'telefon' => ['required', 'string'],
            'adres' => ['required', 'string', 'max:1000'],
            'kalemler' => ['required', 'array', 'min:1'],
            'kalemler.*.urun_id' => ['required', 'integer', 'exists:urunler,id'],
            'kalemler.*.adet' => ['required', 'integer', 'min:1'],
            'kalemler.*.secenekler' => ['nullable', 'array'],
        ])->validate();

        $telefon = Telefon::duzenle($veri['telefon']);

        if (! $telefon) {
            throw ValidationException::withMessages(['telefon' => 'Telefon numarası geçersiz.']);
        }

        return DB::transaction(function () use ($konusmaId, $veri, $telefon) {
            Konusma::whereKey($konusmaId)->lockForUpdate()->firstOrFail();

            $mevcut = Siparis::where('konusma_id', $konusmaId)->first();
            if ($mevcut) {
                return $mevcut;
            }

            $urunIdleri = collect($veri['kalemler'])->pluck('urun_id')->unique();
            $urunler = Urun::whereIn('id', $urunIdleri)->lockForUpdate()->get()->keyBy('id');

            $istenenAdetler = collect($veri['kalemler'])
                ->groupBy('urun_id')
                ->map(fn ($kalemler) => $kalemler->sum('adet'));

            foreach ($istenenAdetler as $urunId => $adet) {
                $urun = $urunler[$urunId];

                if ($urun->stok < $adet) {
                    throw ValidationException::withMessages([
                        'stok' => "{$urun->ad} için yeterli stok yok (stokta {$urun->stok} adet var).",
                    ]);
                }
            }

            $kalemler = [];

            foreach ($veri['kalemler'] as $kalem) {
                $urun = $urunler[$kalem['urun_id']];
                $secilenler = [];

                foreach ($urun->secenekler ?? [] as $ad => $gecerliDegerler) {
                    $deger = $kalem['secenekler'][$ad] ?? null;

                    if (! in_array($deger, $gecerliDegerler, true)) {
                        throw ValidationException::withMessages([
                            'secenekler' => "{$urun->ad} için geçerli bir {$ad} seçilmedi.",
                        ]);
                    }

                    $secilenler[$ad] = $deger;
                }

                $kalemler[] = [
                    'urun_id' => $urun->id,
                    'urun_adi' => $urun->ad,
                    'secenekler' => $secilenler ?: null,
                    'adet' => $kalem['adet'],
                    'birim_fiyat' => $urun->fiyat,
                    'tutar' => round($urun->fiyat * $kalem['adet'], 2),
                ];
            }

            $siparis = Siparis::create([
                'siparis_no' => 'SIP-' . strtoupper(Str::random(8)),
                'konusma_id' => $konusmaId,
                'ad_soyad' => $veri['ad_soyad'],
                'telefon' => $telefon,
                'adres' => $veri['adres'],
                'toplam' => round(array_sum(array_column($kalemler, 'tutar')), 2),
            ]);

            $siparis->kalemler()->createMany($kalemler);

            foreach ($istenenAdetler as $urunId => $adet) {
                $urunler[$urunId]->decrement('stok', $adet);
            }

            return $siparis;
        });
    }
}