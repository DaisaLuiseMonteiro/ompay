<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('transactions', 'type_transaction')) {
            // 1. Supprimer d'abord la contrainte existante
            DB::statement("ALTER TABLE transactions 
                          DROP CONSTRAINT IF EXISTS transactions_type_transaction_check");
            
            // 2. Convertir la colonne en VARCHAR sans contrainte
            DB::statement("ALTER TABLE transactions 
                          ALTER COLUMN type_transaction 
                          TYPE VARCHAR(255) 
                          USING (type_transaction::text)");
            
            // 3. Mettre à jour les valeurs existantes
            DB::table('transactions')
                ->where('type_transaction', 'virement')
                ->update(['type_transaction' => 'transfert']);
                
            // 4. Vérifier les valeurs non valides
            $invalidTypes = DB::table('transactions')
                ->whereNotIn('type_transaction', ['paiement', 'retrait', 'depot', 'transfert', 'virement'])
                ->pluck('type_transaction')
                ->unique()
                ->toArray();
                
            if (!empty($invalidTypes)) {
                throw new \Exception("Valeurs non valides trouvées dans la colonne 'type_transaction': " . implode(', ', $invalidTypes));
            }
            
            // 5. Ajouter la nouvelle contrainte
            DB::statement("ALTER TABLE transactions 
                          ADD CONSTRAINT transactions_type_transaction_check 
                          CHECK (type_transaction IN ('paiement', 'retrait', 'depot', 'transfert', 'virement'))");
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'type_transaction')) {
            // 1. Supprimer la contrainte actuelle
            DB::statement("ALTER TABLE transactions 
                          DROP CONSTRAINT IF EXISTS transactions_type_transaction_check");
            
            // 2. Revenir aux valeurs d'origine
            DB::table('transactions')
                ->where('type_transaction', 'transfert')
                ->update(['type_transaction' => 'virement']);
                
            // 3. Ajouter l'ancienne contrainte
            DB::statement("ALTER TABLE transactions 
                          ADD CONSTRAINT transactions_type_transaction_check 
                          CHECK (type_transaction IN ('paiement', 'virement', 'retrait'))");
        }
    }
};