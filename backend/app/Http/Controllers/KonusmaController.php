<?php

namespace App\Http\Controllers;

use App\Models\Konusma;
use App\Services\TaslakDegerlendirici;
use App\Services\YapayZekaServisi;
use Illuminate\Http\Request;

class KonusmaController extends Controller
{
    public function __construct(
        private YapayZekaServisi $yapayZeka,
        private TaslakDegerlendirici $degerlendirici,
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