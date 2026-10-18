<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiparisKalemi extends Model
{
    protected $table = 'siparis_kalemleri';

    protected $fillable = ['urun_id', 'urun_adi', 'secenekler', 'adet', 'birim_fiyat', 'tutar'];

    protected $casts = [
        'secenekler' => 'array',
        'birim_fiyat' => 'decimal:2',
        'tutar' => 'decimal:2',
    ];
}