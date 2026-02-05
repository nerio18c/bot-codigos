<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('codigos_streaming', function (Blueprint $table) {
            $table->dropUnique('codigos_streaming_email_cuenta_unique');
            $table->unique(['email_cuenta', 'plataforma'], 'codigos_streaming_email_plataforma_unique');
        });
    }

    public function down(): void
    {
        Schema::table('codigos_streaming', function (Blueprint $table) {
            $table->dropUnique('codigos_streaming_email_plataforma_unique');
            $table->unique('email_cuenta', 'codigos_streaming_email_cuenta_unique');
        });
    }
};
