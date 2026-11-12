<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->unique('paiement_id');                       // un reçu = une réservation
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'statut']);                // "mes réservations"
            $table->index(['statut', 'created_at']);             // file de vérification admin
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tel')) {
                $table->string('tel')->nullable();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('client')->index();
            }
            if (! Schema::hasColumn('users', 'actif')) {
                $table->boolean('actif')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'statut']);
            $table->dropIndex(['statut', 'created_at']);
            $table->dropConstrainedForeignId('validated_by');
            $table->dropUnique(['paiement_id']);
        });
    }
};
