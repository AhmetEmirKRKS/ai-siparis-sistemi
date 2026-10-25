<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siparis extends Model
{
    public const DURUM_GECISLERI = [
        'beklemede' => ['hazirlaniyor', 'iptal_edildi'],
        'hazirlaniyor' => ['kargoda', 'iptal_edildi'],
        'kargoda' => ['teslim_edildi'],
        'teslim_edildi' => [],
        'iptal_edildi' => [],
    ];

    protected $table = 'siparisler';

    protected $fillable = ['siparis_no', 'konusma_id', 'ad_soyad', 'telefon', 'adres', 'toplam', 'durum'];

    protected $attributes = [
        'durum' => 'beklemede',
    ];

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

    public function durumaGecebilirMi(string $yeniDurum): bool
    {
        return in_array($yeniDurum, self::DURUM_GECISLERI[$this->durum] ?? [], true);
    }
}