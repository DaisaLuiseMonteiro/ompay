<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Supprimer la contrainte existante
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_transaction_check");
        
        // Recréer la contrainte avec la nouvelle valeur
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_transaction_check 
            CHECK (type_transaction IN ('paiement', 'virement', 'retrait', 'paiement_marchand'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer la contrainte modifiée
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_transaction_check");
        
        // Remettre la contrainte d'origine
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_transaction_check 
            CHECK (type_transaction IN ('paiement', 'virement', 'retrait'))");
    }
};
