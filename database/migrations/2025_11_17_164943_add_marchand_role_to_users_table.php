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
        // Modifier la colonne role pour ajouter 'marchand' aux valeurs possibles
        DB::statement("ALTER TABLE users DROP CONSTRAINT users_role_check");
        
        $types = ['admin', 'agent', 'client', 'marchand'];
        $enumString = "'" . implode("','", $types) . "'";
        
        DB::statement(
            "ALTER TABLE users 
            ADD CONSTRAINT users_role_check 
            CHECK (role::text = ANY (ARRAY[{$enumString}]::text[]))"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revenir aux valeurs d'origine
        DB::statement("ALTER TABLE users DROP CONSTRAINT users_role_check");
        
        $types = ['admin', 'agent', 'client'];
        $enumString = "'" . implode("','", $types) . "'";
        
        DB::statement(
            "ALTER TABLE users 
            ADD CONSTRAINT users_role_check 
            CHECK (role::text = ANY (ARRAY[{$enumString}]::text[]))"
        );
    }
};
