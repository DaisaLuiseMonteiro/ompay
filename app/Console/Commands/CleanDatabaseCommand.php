<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CleanDatabaseCommand extends Command
{
    protected $signature = 'db:clean';
    protected $description = 'Nettoie la base de données en gardant seulement 3 clients, 3 comptes et 6 transactions';

    public function handle()
    {
        if (!$this->confirm('Êtes-vous sûr de vouloir nettoyer la base de données ? Cette action est irréversible.')) {
            $this->info('Opération annulée.');
            return 0;
        }

        try {
            // Désactiver temporairement les contraintes
            DB::statement('SET CONSTRAINTS ALL DEFERRED');

            // Démarrer une transaction
            DB::beginTransaction();

            // 1. Sélectionner 3 clients aléatoires
            $clients = Client::inRandomOrder()->limit(3)->get();
            $clientIds = $clients->pluck('id')->toArray();
            
            // Vérifier que nous avons des clients valides
            if (count($clientIds) === 0) {
                throw new \Exception("Aucun client trouvé dans la base de données.");
            }
            
            // 2. Supprimer les autres clients
            Client::whereNotIn('id', $clientIds)->delete();
            
            // 3. Sélectionner 3 comptes aléatoires
            $comptes = Compte::inRandomOrder()->limit(3)->get();
            $compteIds = $comptes->pluck('id')->toArray();
            
            // Vérifier que nous avons des comptes valides
            if (count($compteIds) === 0) {
                throw new \Exception("Aucun compte trouvé dans la base de données.");
            }
            
            // 4. Supprimer les autres comptes
            Compte::whereNotIn('id', $compteIds)->delete();

            // 5. Sélectionner 3 paiements et 3 transferts
            $paiements = Transaction::where('type', 'paiement')
                ->whereIn('client_id', $clientIds)
                ->inRandomOrder()
                ->limit(3)
                ->get();

            $transferts = Transaction::where('type', 'transfert')
                ->whereIn('client_id', $clientIds)
                ->inRandomOrder()
                ->limit(3)
                ->get();

            $transactionsToKeep = $paiements->merge($transferts);
            $transactionIds = $transactionsToKeep->pluck('id')->toArray();
            
            // 6. Supprimer les autres transactions si on en a trouvé
            if (count($transactionIds) > 0) {
                Transaction::whereNotIn('id', $transactionIds)->delete();
            }

            // Valider la transaction
            DB::commit();

            $this->info('Base de données nettoyée avec succès !');
            $this->info('Clients restants : ' . Client::count());
            $this->info('Comptes restants : ' . Compte::count());
            $this->info('Transactions restantes : ' . Transaction::count());

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Une erreur est survenue : ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}