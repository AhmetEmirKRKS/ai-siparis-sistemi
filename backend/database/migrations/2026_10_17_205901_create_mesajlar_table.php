<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesajlar', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('konusma_id')->constrained('konusmalar')->cascadeOnDelete();
            $table->string('rol');
            $table->text('icerik');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesajlar');
    }
};