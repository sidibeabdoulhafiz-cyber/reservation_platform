<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réponses envoyées depuis l'admin et lien vers l'événement créé en brouillon.
        Schema::table('messages', function (Blueprint $table) {
            $table->json('reponses')->nullable()->after('note_interne');
            $table->timestamp('repondu_at')->nullable()->after('reponses');
            $table->foreignId('evenement_id')->nullable()->after('repondu_at')->constrained('evenements')->nullOnDelete();
        });

        // Catégorie d'événement pour le filtre du site : concert, festival, match, sortie…
        Schema::table('evenements', function (Blueprint $table) {
            $table->string('categorie', 20)->nullable()->after('accroche')->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evenement_id');
            $table->dropColumn(['reponses', 'repondu_at']);
        });
        Schema::table('evenements', function (Blueprint $table) {
            $table->dropIndex(['categorie']);
            $table->dropColumn('categorie');
        });
    }
};
