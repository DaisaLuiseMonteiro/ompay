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
        // Table oauth_access_tokens
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->uuid('new_user_id')->nullable()->after('user_id');
        });

        // Copier les données
        $accessTokens = DB::table('oauth_access_tokens')->get();
        foreach ($accessTokens as $token) {
            $client = DB::table('clients')->where('id', $token->user_id)->first();
            if ($client) {
                DB::table('oauth_access_tokens')
                    ->where('id', $token->id)
                    ->update(['new_user_id' => $client->id]);
            }
        }

        // Supprimer l'ancienne colonne et renommer la nouvelle
        Schema::table('oauth_access_tokens', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->renameColumn('new_user_id', 'user_id');
        });

        // Table oauth_auth_codes
        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->uuid('new_user_id')->nullable()->after('user_id');
        });

        $authCodes = DB::table('oauth_auth_codes')->get();
        foreach ($authCodes as $code) {
            $client = DB::table('clients')->where('id', $code->user_id)->first();
            if ($client) {
                DB::table('oauth_auth_codes')
                    ->where('id', $code->id)
                    ->update(['new_user_id' => $client->id]);
            }
        }

        Schema::table('oauth_auth_codes', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->renameColumn('new_user_id', 'user_id');
        });

        // Table oauth_clients
        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->uuid('new_user_id')->nullable()->after('user_id');
        });

        $clients = DB::table('oauth_clients')->get();
        foreach ($clients as $client) {
            if ($client->user_id) {
                $user = DB::table('clients')->where('id', $client->user_id)->first();
                if ($user) {
                    DB::table('oauth_clients')
                        ->where('id', $client->id)
                        ->update(['new_user_id' => $user->id]);
                }
            }
        }

        Schema::table('oauth_clients', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->renameColumn('new_user_id', 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cette migration ne peut pas être annulée de manière sécurisée
        throw new \RuntimeException('Migration cannot be reversed. Please restore from backup.');
    }
};
