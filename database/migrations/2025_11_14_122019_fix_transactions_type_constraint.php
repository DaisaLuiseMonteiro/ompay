<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer les contraintes existantes
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_transaction_check;");
        
        // Ajouter la nouvelle contrainte avec toutes les valeurs nécessaires
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN ('paiement', 'retrait', 'depot', 'transfert', 'virement'));
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        
        // Remettre l'ancienne contrainte (si nécessaire)
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN ('paiement', 'retrait', 'depot', 'virement'));
        ");
    }
};
