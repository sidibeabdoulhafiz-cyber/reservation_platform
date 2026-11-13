<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('ref', 20)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->foreignId('depart_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('type_billet_id')->nullable()->constrained('types_billets')->restrictOnDelete();
            $table->unsignedTinyInteger('quantite');
            $table->unsignedInteger('prix_unitaire');
            $table->unsignedInteger('montant');
            $table->string('statut', 20)->default('a_payer');
            $table->timestamp('expire_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'statut']);
            $table->index(['statut', 'created_at']);
            $table->index(['statut', 'expire_at']);
        });

        // Contrainte d'intégrité (MySQL 8.0.16+ / PostgreSQL). Ignorée sur SQLite.
        // Blueprint::check() n'existe pas dans Laravel 13 : on passe par du SQL brut.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement("ALTER TABLE reservations ADD CONSTRAINT reservations_type_check CHECK (
                (type = 'transport' AND depart_id IS NOT NULL AND zone_id IS NOT NULL AND type_billet_id IS NULL)
                OR
                (type = 'billet' AND type_billet_id IS NOT NULL AND depart_id IS NULL AND zone_id IS NULL)
            )");
        }

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id', 40)->unique();
            $table->unsignedInteger('montant_declare');
            $table->unsignedInteger('montant_attendu');
            $table->unsignedTinyInteger('quantite_deduite')->nullable();
            $table->string('controle', 20);
            $table->string('capture_path');
            $table->string('capture_hash', 64);
            $table->string('statut', 20)->default('en_verification');
            $table->string('motif_refus')->nullable();
            $table->foreignId('verifie_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifie_at')->nullable();
            $table->timestamps();

            $table->index(['statut', 'created_at']);
            $table->index('capture_hash');
        });

        Schema::create('billets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('numero', 20)->unique();
            $table->foreignId('depart_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('type_billet_id')->nullable()->constrained('types_billets')->restrictOnDelete();
            $table->unsignedSmallInteger('siege')->nullable();
            $table->string('nom_passager')->nullable();
            $table->string('statut', 20)->default('valide');
            $table->timestamp('utilise_at')->nullable();
            $table->foreignId('scanne_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['depart_id', 'siege']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billets');
        Schema::dropIfExists('paiements');
        Schema::dropIfExists('reservations');
    }
};
