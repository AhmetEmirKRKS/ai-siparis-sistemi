<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siparis extends Model
{
    protected $table = 'siparisler';

    protected $fillable = ['siparis_no', 'konusma_id', 'ad_soyad', 'telefon', 'adres', 'toplam', 'durum'];

    protected $casts = [
        'toplam' => 'decimal:2',
    ];

    public function kalemler()
    {
        return $this->hasMany(SiparisKalemi::class);
    }

    public function konusma()
    {
        return $this->belongsTo(Konusma::class);
    }
}