<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Arrêts ordonnés sur l'itinéraire : ordre de passage du car.
        Schema::table('depart_zone', function (Blueprint $table) {
            $table->unsignedSmallInteger('ordre')->default(0)->after('zone_id');
            $table->boolean('principal')->default(false)->after('ordre');
        });

        Schema::table('evenements', function (Blueprint $table) {
            // Lien du groupe WhatsApp communautaire (facultatif pour le passager).
            $table->string('lien_whatsapp')->nullable()->after('image_path');
            // Petit texte d'accroche affiché sur la carte de l'événement.
            $table->string('accroche', 160)->nullable()->after('titre');
        });
    }

    public function down(): void
    {
        Schema::table('depart_zone', function (Blueprint $table) {
            $table->dropColumn(['ordre', 'principal']);
        });

        Schema::table('evenements', function (Blueprint $table) {
            $table->dropColumn(['lien_whatsapp', 'accroche']);
        });
    }
};
