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
            // Supprimer la contrainte de clé étrangère existante
            $table->dropForeign(['compte_id']);
            
            // Recréer la colonne comme nullable
            $table->uuid('compte_id')->nullable()->change();
            
            // Recréer la contrainte de clé étrangère
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('comptes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère
            $table->dropForeign(['compte_id']);
            
            // Recréer la colonne comme non nullable
            $table->uuid('compte_id')->nullable(false)->change();
            
            // Recréer la contrainte de clé étrangère
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('comptes')
                  ->onDelete('cascade');
        });
    }
};
