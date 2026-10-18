<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('siparis_kalemleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siparis_id')->constrained('siparisler')->cascadeOnDelete();
            $table->foreignId('urun_id')->constrained('urunler');
            $table->string('urun_adi');
            $table->json('secenekler')->nullable();
            $table->unsignedInteger('adet');
            $table->decimal('birim_fiyat', 10, 2);
            $table->decimal('tutar', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siparis_kalemleri');
    }
};