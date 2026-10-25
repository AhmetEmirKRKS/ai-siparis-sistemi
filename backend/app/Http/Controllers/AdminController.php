<?php

namespace App\Http\Controllers;

use App\Models\Siparis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function giris(Request $request)
    {
        $veri = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $kullanici = User::where('email', $veri['email'])->first();

        if (! $kullanici || ! Hash::check($veri['password'], $kullanici->password)) {
            throw ValidationException::withMessages(['email' => 'E-posta veya şifre hatalı.']);
        }

        return ['token' => $kullanici->createToken('admin')->plainTextToken];
    }

    public function cikis(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ['message' => 'Çıkış yapıldı.'];
    }

    public function siparisler(Request $request)
    {
        $request->validate([
            'durum' => ['nullable', 'in:' . implode(',', array_keys(Siparis::DURUM_GECISLERI))],
            'baslangic' => ['nullable', 'date'],
            'bitis' => ['nullable', 'date'],
        ]);

        $sorgu = Siparis::orderByDesc('id');

        if ($request->filled('durum')) {
            $sorgu->where('durum', $request->durum);
        }

        if ($request->filled('baslangic')) {
            $sorgu->whereDate('created_at', '>=', $request->baslangic);
        }

        if ($request->filled('bitis')) {
            $sorgu->whereDate('created_at', '<=', $request->bitis);
        }

        return $sorgu->get();
    }

    public function siparisDetay(Siparis $siparis)
    {
        $siparis->load(['kalemler', 'konusma.mesajlar' => fn ($sorgu) => $sorgu->orderBy('id')]);

        return [
            'siparis' => $siparis,
            'sonraki_durumlar' => Siparis::DURUM_GECISLERI[$siparis->durum],
        ];
    }

    public function durumGuncelle(Request $request, Siparis $siparis)
    {
        $veri = $request->validate([
            'durum' => ['required', 'string'],
        ]);

        if (! $siparis->durumaGecebilirMi($veri['durum'])) {
            return response()->json([
                'message' => "Sipariş \"{$siparis->durum}\" durumundan \"{$veri['durum']}\" durumuna geçirilemez.",
            ], 422);
        }

        $siparis->update(['durum' => $veri['durum']]);

        return $this->siparisDetay($siparis);
    }
}