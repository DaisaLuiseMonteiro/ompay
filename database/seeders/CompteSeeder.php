<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer uniquement les clients actifs
        $clients = Client::where('statut', 'actif')->get();

        foreach ($clients as $client) {
            // Créer un seul compte par client en XOF
            $soldeInitial = rand(10000, 5000000); // 10 000 à 5 000 000 XOF

            Compte::factory()
                ->for($client)
                ->devise('XOF')
                ->soldeInitial($soldeInitial)
                ->actif()
                ->create();
        }
    }
}
