<?php

namespace Database\Seeders;

use App\Models\Urun;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'admin123']
        );

        $urunler = [
            [
                'ad' => 'Basic Tişört',
                'fiyat' => 249.90,
                'stok' => 50,
                'secenekler' => ['beden' => ['S', 'M', 'L', 'XL'], 'renk' => ['Beyaz', 'Siyah', 'Lacivert']],
            ],
            [
                'ad' => 'Oversize Tişört',
                'fiyat' => 299.90,
                'stok' => 30,
                'secenekler' => ['beden' => ['S', 'M', 'L', 'XL'], 'renk' => ['Beyaz', 'Siyah']],
            ],
            [
                'ad' => 'Kot Pantolon',
                'fiyat' => 599.90,
                'stok' => 20,
                'secenekler' => ['beden' => ['30', '32', '34', '36']],
            ],
            [
                'ad' => 'Kapüşonlu Sweatshirt',
                'fiyat' => 449.90,
                'stok' => 15,
                'secenekler' => ['beden' => ['S', 'M', 'L', 'XL'], 'renk' => ['Gri', 'Siyah']],
            ],
            [
                'ad' => 'Beyzbol Şapkası',
                'fiyat' => 149.90,
                'stok' => 3,
                'secenekler' => ['renk' => ['Siyah', 'Kırmızı']],
            ],
            [
                'ad' => 'Spor Çorap 3lü Paket',
                'fiyat' => 99.90,
                'stok' => 100,
                'secenekler' => null,
            ],
            [
                'ad' => 'Bez Çanta',
                'fiyat' => 79.90,
                'stok' => 0,
                'secenekler' => null,
            ],
        ];

        foreach ($urunler as $urun) {
            Urun::firstOrCreate(['ad' => $urun['ad']], $urun);
        }
    }
}