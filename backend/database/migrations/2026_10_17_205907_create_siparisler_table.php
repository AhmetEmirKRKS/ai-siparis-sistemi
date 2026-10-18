<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siparisler', function (Blueprint $table) {
            $table->id();
            $table->string('siparis_no')->unique();
            $table->foreignUuid('konusma_id')->unique()->constrained('konusmalar');
            $table->string('ad_soyad');
            $table->string('telefon');
            $table->text('adres');
            $table->decimal('toplam', 10, 2);
            $table->string('durum')->default('beklemede');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siparisler');
    }
};