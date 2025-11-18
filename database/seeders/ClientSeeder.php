<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création de 10 clients actifs
        $sexes = ['M', 'F'];
        
        // Création d'un client admin avec un OTP valide
        $adminClient = Client::factory()
            ->actif()
            ->withOtp('sms')
            ->create([
                'nom' => 'Admin',
                'prenom' => 'System',
                'sexe' => 'M',
                'telephone' => '775312571',
                'email' => 'admin@ompay.com',
                'cni' => 'CI00000000A001',
                'password' => bcrypt('admin123'), // Mot de passe fort pour l'admin
            ]);

        // Création d'un compte pour l'admin
        Compte::create([
            'id' => (string) Str::uuid(),
            'client_id' => $adminClient->id,
            'numero_compte' => 'OM' . now()->format('Ymd') . '001',
            'solde_initial' => 100000, // Solde initial élevé pour l'admin
            'devise' => 'XOF',
            'statut' => 'actif',
            'date_ouverture' => now(),
            'code_secret' => '1234', // Code secret pour l'admin
        ]);

        // Création d'un client avec un OTP valide
        $client1 = Client::factory()
            ->actif()
            ->withOtp('sms')
            ->create([
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'sexe' => 'M',
                'telephone' => '775312572',
                'email' => 'jean.dupont@example.com',
                'cni' => 'CI00000001M001',
            ]);

        // Création d'un compte pour le client 1
        Compte::create([
            'id' => (string) Str::uuid(),
            'client_id' => $client1->id,
            'numero_compte' => 'OM' . now()->format('Ymd') . '002',
            'solde_initial' => 50000,
            'devise' => 'XOF',
            'statut' => 'actif',
            'date_ouverture' => now(),
            'code_secret' => '5678',
        ]);

        // Création d'un client avec un OTP expiré
        $client2 = Client::factory()
            ->actif()
            ->withExpiredOtp('email')
            ->create([
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'sexe' => 'F',
                'telephone' => '775312573',
                'email' => 'sophie.martin@example.com',
                'cni' => 'CI00000002F001',
            ]);

        // Création d'un compte pour le client 2
        Compte::create([
            'id' => (string) Str::uuid(),
            'client_id' => $client2->id,
            'numero_compte' => 'OM' . now()->format('Ymd') . '003',
            'solde_initial' => 30000,
            'devise' => 'XOF',
            'statut' => 'actif',
            'date_ouverture' => now(),
            'code_secret' => '9012',
        ]);

        // Création de 7 autres clients actifs avec leurs comptes
        for ($i = 3; $i <= 10; $i++) {
            $sexe = $sexes[array_rand($sexes)];
            $client = Client::factory()
                ->actif()
                ->create([
                    'password' => bcrypt('password'), // Mot de passe par défaut
                    'sexe' => $sexe,
                    'cni' => 'CI' . str_pad($i, 8, '0', STR_PAD_LEFT) . 
                             $sexe . 
                             str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT)
                ]);

            // Création d'un compte pour chaque client
            Compte::create([
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'numero_compte' => 'OM' . now()->format('Ymd') . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'solde_initial' => rand(1000, 50000),
                'devise' => 'XOF',
                'statut' => 'actif',
                'date_ouverture' => now(),
                'code_secret' => (string) rand(1000, 9999),
            ]);
        }
    }
}