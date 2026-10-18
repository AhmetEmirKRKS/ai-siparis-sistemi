<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Urun extends Model
{
    protected $table = 'urunler';

    protected $fillable = ['ad', 'fiyat', 'stok', 'secenekler'];

    protected $casts = [
        'fiyat' => 'decimal:2',
        'secenekler' => 'array',
    ];
}