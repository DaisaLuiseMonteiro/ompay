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
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference')->unique();
            $table->enum('type_transaction', ['paiement', 'virement', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->decimal('frais', 15, 2)->default(0);
            $table->string('devise', 3)->default('XOF');
            $table->text('description')->nullable();
            
            // Compte source (toujours présent)
            $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');
            
            // Compte destinataire (uniquement pour les virements)
            $table->foreignUuid('compte_destinataire_id')
                  ->nullable()
                  ->constrained('comptes')
                  ->nullOnDelete();
            
            $table->enum('statut', ['en_attente', 'validee', 'annulee', 'echec'])->default('validee');
            $table->dateTime('date_transaction');
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('compte_id');
            $table->index('compte_destinataire_id');
            $table->index('reference');
            $table->index('type_transaction');
            $table->index('statut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
