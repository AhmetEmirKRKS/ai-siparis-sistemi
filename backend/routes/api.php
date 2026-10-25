<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\KonusmaController;
use Illuminate\Support\Facades\Route;

Route::post('/konusmalar', [KonusmaController::class, 'olustur']);
Route::get('/konusmalar/{konusma}', [KonusmaController::class, 'goster']);
Route::post('/konusmalar/{konusma}/mesajlar', [KonusmaController::class, 'mesajGonder']);
Route::post('/konusmalar/{konusma}/onayla', [KonusmaController::class, 'onayla']);

Route::post('/admin/giris', [AdminController::class, 'giris'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('/cikis', [AdminController::class, 'cikis']);
    Route::get('/siparisler', [AdminController::class, 'siparisler']);
    Route::get('/siparisler/{siparis}', [AdminController::class, 'siparisDetay']);
    Route::patch('/siparisler/{siparis}/durum', [AdminController::class, 'durumGuncelle']);
});