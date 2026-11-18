<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Noms des passagers saisis par le payeur (invités sans compte). Un nom par place.
        Schema::table('reservations', function (Blueprint $table) {
            $table->json('passagers')->nullable()->after('quantite');
        });

        // Contrôles distincts à l'aller et au retour (billet de transport aller-retour).
        Schema::table('billets', function (Blueprint $table) {
            $table->timestamp('aller_scanne_at')->nullable()->after('utilise_at');
            $table->foreignId('aller_scanne_par')->nullable()->after('aller_scanne_at')->constrained('users')->nullOnDelete();
            $table->timestamp('retour_scanne_at')->nullable()->after('aller_scanne_par');
            $table->foreignId('retour_scanne_par')->nullable()->after('retour_scanne_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('billets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aller_scanne_par');
            $table->dropConstrainedForeignId('retour_scanne_par');
            $table->dropColumn(['aller_scanne_at', 'retour_scanne_at']);
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('passagers');
        });
    }
};
