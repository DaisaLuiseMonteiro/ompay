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
        // Mettre à jour les transactions existantes sans devise
        \DB::table('transactions')
            ->whereNull('devise')
            ->update(['devise' => 'XOF']);

        // Modifier la colonne pour définir la valeur par défaut et ajouter une contrainte
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('devise', 3)
                  ->default('XOF')
                  ->comment('Code devise selon la norme ISO 4217')
                  ->change();
        });

        // Ajouter une contrainte de vérification pour s'assurer que seule la devise XOF est utilisée
        \DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT check_devise_xof 
            CHECK (devise = 'XOF')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte de vérification
        \DB::statement('ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_devise_xof');

        // Rétablir la colonne à son état d'origine
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('devise', 3)
                  ->default('XOF')
                  ->nullable()
                  ->change();
        });
    }
};
