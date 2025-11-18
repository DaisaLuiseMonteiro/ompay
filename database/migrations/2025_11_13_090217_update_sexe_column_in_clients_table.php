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
        // Mettre à jour les valeurs NULL existantes avec 'M' comme valeur par défaut
        DB::table('clients')->whereNull('sexe')->update(['sexe' => 'M']);
        
        // Rendre la colonne non nullable
        Schema::table('clients', function (Blueprint $table) {
            $table->string('sexe')->nullable(false)->default('M')->change();
            
            // Ajouter une contrainte CHECK pour s'assurer que la valeur est soit 'M' soit 'F'
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE clients ADD CONSTRAINT chk_sexe CHECK (sexe IN ('M', 'F'))");
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Supprimer la contrainte CHECK si elle existe
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS chk_sexe');
            }
            
            // Rendre la colonne nullable à nouveau
            $table->string('sexe')->nullable()->default(null)->change();
        });
    }
};
