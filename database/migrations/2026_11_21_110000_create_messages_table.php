<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Messages reçus via le site : demandes d'organisateurs et prises de contact.
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index(); // organisateur | contact
            $table->string('nom', 120);
            $table->string('email', 190);
            $table->string('tel', 30)->nullable();
            $table->string('organisation', 150)->nullable();
            $table->string('evenement', 150)->nullable();
            $table->date('date_evenement')->nullable();
            $table->string('lieu', 150)->nullable();
            $table->unsignedInteger('participants')->nullable();
            $table->string('sujet', 150)->nullable();
            $table->text('message')->nullable();
            $table->string('statut', 20)->default('nouveau')->index(); // nouveau | en_cours | traite | refuse
            $table->text('note_interne')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
