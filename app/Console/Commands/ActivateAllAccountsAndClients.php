<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateAllAccountsAndClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:activate-all-accounts-and-clients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Active tous les comptes et clients inactifs dans la base de données';

    /**
     * Execute the console command.
     */
public function handle()
{
    // Utiliser une transaction pour s'assurer de l'intégrité des données
    DB::beginTransaction();

    try {
        // Activer tous les clients inactifs
        $clientsUpdated = Client::where('statut', '!=', 'actif')
            ->update(['statut' => 'actif']);

        // Activer tous les comptes inactifs
        $comptesUpdated = Compte::where('statut', '!=', 'actif')
            ->update(['statut' => 'actif']);

        // Valider la transaction
        DB::commit();

        $this->info("Mise à jour terminée avec succès !");
        $this->line("Clients activés : $clientsUpdated");
        $this->line("Comptes activés : $comptesUpdated");

        return Command::SUCCESS;
    } catch (\Exception $e) {
        // En cas d'erreur, annuler les modifications
        DB::rollBack();
        $this->error("Une erreur est survenue : " . $e->getMessage());
        return Command::FAILURE;
    }
}
}
