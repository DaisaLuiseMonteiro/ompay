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
        Schema::table('marchands', function (Blueprint $table) {
            $table->uuid('compte_id')->nullable()->after('id');
            
            // Clé étrangère vers la table comptes
            $table->foreign('compte_id')
                  ->references('id')
                  ->on('comptes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marchands', function (Blueprint $table) {
            $table->dropForeign(['compte_id']);
            $table->dropColumn('compte_id');
        });
    }
};
