<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rappels automatiques avant le départ (J-1 et H-2), envoyés une seule fois.
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('rappel_veille_at')->nullable();
            $table->timestamp('rappel_depart_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['rappel_veille_at', 'rappel_depart_at']);
        });
    }
};
