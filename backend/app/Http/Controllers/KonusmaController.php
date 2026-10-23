<?php

namespace App\Http\Controllers;

use App\Models\Konusma;
use App\Services\SiparisServisi;
use App\Services\TaslakDegerlendirici;
use App\Services\YapayZekaServisi;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KonusmaController extends Controller
{
    public function __construct(
        private YapayZekaServisi $yapayZeka,
        private TaslakDegerlendirici $degerlendirici,
        private SiparisServisi $siparisServisi,
    ) {
    }

    public function olustur()
    {
        $konusma = Konusma::create();

        return $this->durum($konusma);
    }

    public function goster(Konusma $konusma)
    {
        return $this->durum($konusma);
    }

    public function mesajGonder(Request $request, Konusma $konusma)
    {
        $veri = $request->validate([
            'mesaj' => ['required', 'string', 'max:1000'],
        ]);

        if ($konusma->siparis) {
            return response()->json(['message' => 'Bu konuşmadaki sipariş zaten oluşturuldu.'], 422);
        }

        $gecmis = $konusma->mesajlar()->latest('id')->take(10)->get()->reverse()
            ->map(fn ($m) => ['rol' => $m->rol, 'icerik' => $m->icerik])
            ->values()
            ->all();

        $konusma->mesajlar()->create(['rol' => 'kullanici', 'icerik' => $veri['mesaj']]);

        $yeniTaslak = $this->yapayZeka->taslakCikar($konusma->taslak, $gecmis, $veri['mesaj']);

        if ($yeniTaslak === null) {
            $cevap = 'Mesajınızı şu an işleyemedim. Tekrar yazar mısınız?';
        } else {
            $konusma->update(['taslak' => $yeniTaslak]);
            $cevap = $this->degerlendirici->cevapYaz($this->degerlendirici->degerlendir($yeniTaslak));
        }

        $konusma->mesajlar()->create(['rol' => 'asistan', 'icerik' => $cevap]);

        return $this->durum($konusma);
    }

    public function onayla(Konusma $konusma)
    {
        if ($konusma->siparis) {
            return $this->durum($konusma);
        }

        $sonuc = $this->degerlendirici->degerlendir($konusma->taslak);

        if ($sonuc['sorular']) {
            return response()->json(['message' => 'Sipariş bilgileri henüz tamamlanmadı.'], 422);
        }

        try {
            $siparis = $this->siparisServisi->olustur($konusma->id, $sonuc);

            if ($siparis->wasRecentlyCreated) {
                $konusma->mesajlar()->create([
                    'rol' => 'asistan',
                    'icerik' => "Siparişiniz alındı. Sipariş numaranız: {$siparis->siparis_no}",
                ]);
            }
        } catch (ValidationException $e) {
            $konusma->mesajlar()->create([
                'rol' => 'asistan',
                'icerik' => 'Sipariş oluşturulamadı: ' . collect($e->errors())->flatten()->join(' '),
            ]);
        }

        return $this->durum($konusma->fresh());
    }

    private function durum(Konusma $konusma)
    {
        $siparis = $konusma->siparis;
        $sonuc = $this->degerlendirici->degerlendir($konusma->taslak);

        return response()->json([
            'id' => $konusma->id,
            'mesajlar' => $konusma->mesajlar()->orderBy('id')->get(['rol', 'icerik']),
            'onaya_hazir' => ! $siparis && empty($sonuc['sorular']),
            'siparis_no' => $siparis?->siparis_no,
        ]);
    }
}