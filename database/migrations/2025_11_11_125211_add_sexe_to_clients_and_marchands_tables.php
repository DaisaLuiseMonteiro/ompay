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
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->after('cni')->comment('M pour Masculin, F pour Féminin');
        });

        Schema::table('marchands', function (Blueprint $table) {
            $table->enum('sexe', ['M', 'F'])->after('prenom')->comment('M pour Masculin, F pour Féminin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('sexe');
        });

        Schema::table('marchands', function (Blueprint $table) {
            $table->dropColumn('sexe');
        });
    }
};
