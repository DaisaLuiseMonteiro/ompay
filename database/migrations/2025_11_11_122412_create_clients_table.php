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
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('prenom');
            $table->date('date_naissance');
            $table->string('adresse')->nullable();
            $table->string('telephone')->unique();
            $table->string('cni')->unique();
            $table->string('code_secret', 4);
            $table->string('statut')->default('actif');
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index('telephone');
            $table->index('cni');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
