<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Konusma extends Model
{
    use HasUuids;

    protected $table = 'konusmalar';

    protected $fillable = ['taslak'];

    protected $casts = [
        'taslak' => 'array',
    ];

    public function mesajlar()
    {
        return $this->hasMany(Mesaj::class);
    }

    public function siparis()
    {
        return $this->hasOne(Siparis::class);
    }
}