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
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('numero_compte')->unique();
            $table->decimal('solde_initial', 15, 2)->default(0);
            $table->string('devise', 3)->default('XOF');
            $table->string('statut')->default('actif');
            $table->date('date_ouverture');
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('client_id');
            $table->index('numero_compte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
