<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Supprimer l'ancienne contrainte
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        
        // Ajouter la nouvelle contrainte avec 'transfert' inclus
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN ('paiement', 'virement', 'retrait', 'depot', 'transfert'));
        ");
    }

    public function down()
    {
        // Supprimer la contrainte
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        
        // Remettre l'ancienne contrainte
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN ('paiement', 'virement', 'retrait', 'depot'));
        ");
    }
};