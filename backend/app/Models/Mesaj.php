<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesaj extends Model
{
    protected $table = 'mesajlar';

    protected $fillable = ['rol', 'icerik'];
}