<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Ajouter le champ type_operation pour indiquer si c'est un débit (-) ou crédit (+)
            $table->enum('type_operation', ['debit', 'credit'])->nullable()->after('type');
            
            // Ajouter un index pour les requêtes de filtrage
            $table->index('type_operation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['type_operation']);
            $table->dropColumn('type_operation');
        });
    }
};
