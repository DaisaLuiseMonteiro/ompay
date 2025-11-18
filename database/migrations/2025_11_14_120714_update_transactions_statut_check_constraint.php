<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Supprimer l'ancienne contrainte
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_statut_check;");
        
        // Ajouter la nouvelle contrainte
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_statut_check 
            CHECK (statut IN ('en_attente', 'validee', 'annulee', 'echec', 'terminee'));
        ");
    }

    public function down()
    {
        // Supprimer la contrainte
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_statut_check;");
        
        // Remettre l'ancienne contrainte
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_statut_check 
            CHECK (statut IN ('en_attente', 'validee', 'annulee', 'echec'));
        ");
    }
};