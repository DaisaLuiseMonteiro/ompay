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
            // Vérifier si la colonne type_transaction existe avant de la renommer
            if (Schema::hasColumn('transactions', 'type_transaction')) {
                $table->renameColumn('type_transaction', 'type');
            }
            
            // Ajouter les colonnes manquantes si elles n'existent pas déjà
            if (!Schema::hasColumn('transactions', 'client_id')) {
                $table->uuid('client_id')->nullable()->after('compte_id');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('transactions', 'solde_avant')) {
                $table->decimal('solde_avant', 15, 2)->nullable()->after('frais');
            }
            
            if (!Schema::hasColumn('transactions', 'solde_apres')) {
                $table->decimal('solde_apres', 15, 2)->nullable()->after('solde_avant');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Ne rien faire dans le down pour éviter de perdre des données
            // Cette migration est sécurisée car elle vérifie l'existence des colonnes avant de les modifier
        });
    }
};
