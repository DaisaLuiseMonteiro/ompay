<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Marchand;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Création de 3 clients
        $clients = Client::factory(3)->create();

        // Pour chaque client, créer un compte
        $comptes = $clients->map(function ($client) {
            return Compte::factory()->create([
                'client_id' => $client->id,
                'solde_initial' => 100000, // 1000.00 XOF
                'statut' => 'actif',
                'date_ouverture' => now() // Champ requis
            ]);
        });

        // Création de 3 marchands
        $marchands = Marchand::factory(3)->create();

        // Création de 3 transactions de paiement
        foreach ($marchands as $index => $marchand) {
            Transaction::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'reference' => 'PAY' . time() . $index,
                'type_transaction' => 'paiement',
                'montant' => rand(1000, 10000) / 100, // 10.00 à 100.00 XOF
                'frais' => 50.00, // Frais fixes de 50 XOF
                'devise' => 'XOF',
                'description' => 'Paiement marchand ' . $marchand->nom_commerce,
                'compte_id' => $comptes[$index % 3]->id,
                'statut' => 'validee',
                'date_transaction' => now()->subDays(rand(1, 30)),
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30))
            ]);
        }

        // Création de 3 transactions de virement
        for ($i = 0; $i < 3; $i++) {
            $expediteur = $comptes[$i % 3];
            $destinataire = $comptes[($i + 1) % 3];
            $montant = rand(1000, 5000) / 100; // 10.00 à 50.00 XOF

            // Créer la transaction de virement
            Transaction::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'reference' => 'VIR' . time() . $i,
                'type_transaction' => 'virement',
                'montant' => $montant,
                'frais' => 25.00, // Frais de virement
                'devise' => 'XOF',
                'description' => 'Virement vers ' . $destinataire->client->nom,
                'compte_id' => $expediteur->id,
                'compte_destinataire_id' => $destinataire->id,
                'statut' => 'validee',
                'date_transaction' => now()->subDays(rand(1, 30)),
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30))
            ]);
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('Clients : ' . Client::count());
        $this->command->info('Comptes : ' . Compte::count());
        $this->command->info('Marchands : ' . Marchand::count());
        $this->command->info('Transactions : ' . Transaction::count());
    }
}