<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Exécuter les seeders dans l'ordre
        $this->call([
            UserSeeder::class,          // D'abord créer les utilisateurs
            ClientSeeder::class,        // Puis les clients
            MarchandSeeder::class,      // Puis les marchands
            CompteSeeder::class,        // Puis les comptes
            TransactionSeeder::class,   // Puis les transactions
            SampleDataSeeder::class,    // Puis les données de test
            UpdateComptesWithCodeSecret::class, // Mettre à jour les codes secrets
        ]);
    }
}
