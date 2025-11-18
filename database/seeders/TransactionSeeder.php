<?php

namespace Database\Seeders;

use App\Models\Compte;
use App\Models\Transaction;
use App\Models\Marchand;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les comptes actifs
        $comptes = Compte::with('client')
            ->where('statut', 'actif')
            ->get();

        // Vérifier qu'il y a au moins 2 comptes
        if ($comptes->count() < 2) {
            $this->command->warn('Pas assez de comptes actifs pour créer des transactions.');
            return;
        }

        // Récupérer les marchands
        $marchands = Marchand::all();

        // Créer 3 transactions de paiement marchand
        for ($i = 0; $i < 3; $i++) {
            $compte = $comptes->random();
            $marchand = $marchands->random();
            
            Transaction::create([
                'reference' => 'TRX' . now()->format('YmdHis') . strtoupper(\Illuminate\Support\Str::random(6)),
                'type' => 'paiement',
                'montant' => $this->generateRandomAmount('paiement', 'XOF'),
                'frais' => 0,
                'devise' => 'XOF',
                'description' => 'Paiement chez ' . $marchand->nom_commerce,
                'compte_id' => $compte->id,
                'marchand_id' => $marchand->id,
                'client_id' => $compte->client_id,
                'solde_avant' => $compte->solde_initial + $this->generateRandomAmount('paiement', 'XOF'),
                'solde_apres' => $compte->solde_initial,
                'statut' => 'validee',
                'date_transaction' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Créer 3 virements entre comptes
        for ($i = 0; $i < 3; $i++) {
            $compteSource = $comptes->random();
            $compteDestinataire = $comptes->where('id', '!=', $compteSource->id)->first();
            
            if (!$compteDestinataire) {
                $this->command->warn('Pas assez de comptes pour effectuer un virement.');
                continue;
            }

            Transaction::create([
                'reference' => 'TRX' . now()->format('YmdHis') . strtoupper(\Illuminate\Support\Str::random(6)),
                'type' => 'virement',
                'montant' => $this->generateRandomAmount('virement', 'XOF'),
                'frais' => 0,
                'devise' => 'XOF',
                'description' => 'Virement à ' . $compteDestinataire->client->prenom . ' ' . $compteDestinataire->client->nom,
                'compte_id' => $compteSource->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'client_id' => $compteSource->client_id,
                'solde_avant' => $compteSource->solde_initial + $this->generateRandomAmount('virement', 'XOF'),
                'solde_apres' => $compteSource->solde_initial,
                'statut' => 'validee',
                'date_transaction' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function generateRandomAmount(string $type, string $devise): float
    {
        if ($type === 'paiement_marchand') {
            // Montants plus petits pour les paiements marchands
            $min = 1000;    // 10.00 XOF
            $max = 50000;   // 500.00 XOF
        } else {
            // Montants plus importants pour les virements
            $min = 1000;    // 10.00 XOF
            $max = 100000;  // 1000.00 XOF
        }
        
        return (float) number_format(rand($min, $max), 2, '.', '');
    }
}