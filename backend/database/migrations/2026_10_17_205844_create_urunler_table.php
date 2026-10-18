<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urunler', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->decimal('fiyat', 10, 2);
            $table->unsignedInteger('stok')->default(0);
            $table->json('secenekler')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urunler');
    }
};