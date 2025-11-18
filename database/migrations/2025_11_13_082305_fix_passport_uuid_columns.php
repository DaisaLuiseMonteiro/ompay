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
        // Désactiver temporairement les contraintes de clé étrangère
        Schema::disableForeignKeyConstraints();

        // Table oauth_access_tokens
        if (Schema::hasTable('oauth_access_tokens')) {
            // Créer une nouvelle table temporaire
            Schema::create('temp_oauth_access_tokens', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->uuid('user_id')->nullable()->index();
                $table->unsignedBigInteger('client_id');
                $table->string('name')->nullable();
                $table->text('scopes')->nullable();
                $table->boolean('revoked');
                $table->timestamps();
                $table->dateTime('expires_at')->nullable();
            });

            // Copier les données avec conversion de type
            DB::statement('INSERT INTO temp_oauth_access_tokens (id, user_id, client_id, name, scopes, revoked, created_at, updated_at, expires_at) SELECT id, user_id::text::uuid, client_id, name, scopes, revoked, created_at, updated_at, expires_at FROM oauth_access_tokens');
            
            // Supprimer l'ancienne table
            Schema::drop('oauth_access_tokens');
            
            // Renommer la nouvelle table
            Schema::rename('temp_oauth_access_tokens', 'oauth_access_tokens');
        }

        // Table oauth_auth_codes
        if (Schema::hasTable('oauth_auth_codes')) {
            Schema::create('temp_oauth_auth_codes', function (Blueprint $table) {
                $table->string('id', 100)->primary();
                $table->uuid('user_id')->index();
                $table->unsignedBigInteger('client_id');
                $table->text('scopes')->nullable();
                $table->boolean('revoked');
                $table->dateTime('expires_at')->nullable();
            });

            // Copier les données avec conversion de type
            DB::statement('INSERT INTO temp_oauth_auth_codes (id, user_id, client_id, scopes, revoked, expires_at) SELECT id, user_id::text::uuid, client_id, scopes, revoked, expires_at FROM oauth_auth_codes');
            
            // Supprimer l'ancienne table
            Schema::drop('oauth_auth_codes');
            
            // Renommer la nouvelle table
            Schema::rename('temp_oauth_auth_codes', 'oauth_auth_codes');
        }

        // Table oauth_clients
        if (Schema::hasTable('oauth_clients')) {
            Schema::create('temp_oauth_clients', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('user_id')->nullable()->index();
                $table->string('name');
                $table->string('secret', 100)->nullable();
                $table->string('provider')->nullable();
                $table->text('redirect');
                $table->boolean('personal_access_client');
                $table->boolean('password_client');
                $table->boolean('revoked');
                $table->timestamps();
            });

            // Copier les données avec conversion de type
            DB::statement('INSERT INTO temp_oauth_clients (id, user_id, name, secret, provider, redirect, personal_access_client, password_client, revoked, created_at, updated_at) SELECT id, user_id::text::uuid, name, secret, provider, redirect, personal_access_client, password_client, revoked, created_at, updated_at FROM oauth_clients');
            
            // Supprimer l'ancienne table
            Schema::drop('oauth_clients');
            
            // Renommer la nouvelle table
            Schema::rename('temp_oauth_clients', 'oauth_clients');
        }

        // Réactiver les contraintes de clé étrangère
        Schema::enableForeignKeyConstraints();
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
