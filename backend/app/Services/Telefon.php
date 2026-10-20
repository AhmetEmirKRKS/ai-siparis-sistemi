<?php

namespace App\Services;

class Telefon
{
    public static function duzenle(?string $telefon): ?string
    {
        $rakamlar = preg_replace('/\D/', '', $telefon ?? '');

        if (strlen($rakamlar) === 12 && str_starts_with($rakamlar, '90')) {
            $rakamlar = substr($rakamlar, 2);
        }

        if (strlen($rakamlar) === 10) {
            $rakamlar = '0' . $rakamlar;
        }

        return preg_match('/^05\d{9}$/', $rakamlar) ? $rakamlar : null;
    }
}