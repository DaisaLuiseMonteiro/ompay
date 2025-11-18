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
        // Drop the existing constraint
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        
        // Add the new constraint with 'transfert' included
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
        // Drop the new constraint
        DB::statement("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS transactions_type_check;");
        
        // Restore the old constraint
        DB::statement("
            ALTER TABLE transactions 
            ADD CONSTRAINT transactions_type_check 
            CHECK (type IN ('paiement', 'retrait', 'depot', 'virement'));
        ");
    }
};
