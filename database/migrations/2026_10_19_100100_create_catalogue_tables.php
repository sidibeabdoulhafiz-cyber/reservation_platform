<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('zones');

        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('lieu');
            $table->string('ville')->default('Abidjan');
            $table->dateTime('debut_at');
            $table->dateTime('fin_at')->nullable();
            $table->string('image_path')->nullable();
            $table->string('statut', 20)->default('brouillon');
            $table->timestamps();

            $table->index(['statut', 'debut_at']);
        });

        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('immatriculation')->nullable()->unique();
            $table->unsignedTinyInteger('capacite');
            $table->string('chauffeur_nom')->nullable();
            $table->string('chauffeur_tel', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique();
            $table->unsignedInteger('prix_defaut')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('departs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicule_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('depart_at');
            $table->dateTime('retour_at')->nullable();
            $table->unsignedSmallInteger('places_total');
            $table->unsignedSmallInteger('places_reservees')->default(0);
            $table->unsignedTinyInteger('max_par_commande')->default(8);
            $table->string('statut', 20)->default('ouvert');
            $table->timestamps();

            $table->index(['evenement_id', 'depart_at']);
            $table->index(['statut', 'depart_at']);
        });

        Schema::create('depart_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('prix');
            $table->string('point_rdv')->nullable();
            $table->time('heure_ramassage')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['depart_id', 'zone_id']);
        });

        Schema::create('types_billets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evenement_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->unsignedInteger('prix');
            $table->unsignedInteger('quota');
            $table->unsignedInteger('vendus')->default(0);
            $table->unsignedTinyInteger('max_par_commande')->default(2);
            $table->dateTime('vente_debut_at')->nullable();
            $table->dateTime('vente_fin_at')->nullable();
            $table->boolean('actif')->default(true);
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_billets');
        Schema::dropIfExists('depart_zone');
        Schema::dropIfExists('departs');
        Schema::dropIfExists('vehicules');
        Schema::dropIfExists('evenements');
        Schema::dropIfExists('zones');
    }
};
