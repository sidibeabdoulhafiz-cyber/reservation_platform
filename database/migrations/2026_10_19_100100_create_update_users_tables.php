<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tel')) {
                $table->string('tel', 20)->nullable()->after('email')->index();
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 20)->default('client')->after('email')->index();
            }
            if (! Schema::hasColumn('users', 'actif')) {
                $table->boolean('actif')->default(true);
            }
        });
    }

    public function down(): void
    {
        // Conservé : les colonnes existent déjà via les migrations précédentes.
    }
};
