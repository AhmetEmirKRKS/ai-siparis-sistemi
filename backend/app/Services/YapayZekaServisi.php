<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YapayZekaServisi
{
    public function taslakCikar(?array $taslak, array $gecmis, string $mesaj): ?array
    {
        try {
            $cevap = Http::timeout(30)->post(config('services.yapay_zeka.url') . '/cikar', [
                'taslak' => $taslak,
                'gecmis' => $gecmis,
                'mesaj' => $mesaj,
            ]);
        } catch (\Exception $e) {
            Log::error('Yapay zeka servisine ulasilamadi: ' . $e->getMessage());
            return null;
        }

        if ($cevap->failed()) {
            Log::warning('Yapay zeka servisi hata dondu', [
                'durum' => $cevap->status(),
                'cevap' => $cevap->body(),
            ]);
            return null;
        }

        return $cevap->json();
    }
}