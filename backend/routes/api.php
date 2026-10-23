<?php

use App\Http\Controllers\KonusmaController;
use Illuminate\Support\Facades\Route;

Route::post('/konusmalar', [KonusmaController::class, 'olustur']);
Route::get('/konusmalar/{konusma}', [KonusmaController::class, 'goster']);
Route::post('/konusmalar/{konusma}/mesajlar', [KonusmaController::class, 'mesajGonder']);
Route::post('/konusmalar/{konusma}/onayla', [KonusmaController::class, 'onayla']);