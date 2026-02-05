<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('codigos_streaming')) {
            return;
        }

        Schema::create('codigos_streaming', function (Blueprint $table) {
            $table->id();
            $table->string('email_cuenta')->unique(); // Correo original (Hotmail/Gmail)
            $table->string('pin');                   // Código de 6 dígitos
            $table->string('plataforma')->nullable(); // Netflix, Amazon, etc.
            $table->timestamps();                    // Para saber qué tan viejo es el código
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_streaming');
    }
};
