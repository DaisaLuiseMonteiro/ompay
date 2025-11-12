<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateCurrencyToXof extends Command
{
    protected $signature = 'app:update-currency-to-xof';
    protected $description = 'Met à jour toutes les devises en XOF dans la base de données';

    public function handle()
    {
        // Mettre à jour les comptes
        $updated = DB::table('comptes')
            ->where('devise', '!=', 'XOF')
            ->orWhereNull('devise')
            ->update(['devise' => 'XOF']);

        $this->info("Mise à jour terminée ! $updated comptes ont été mis à jour avec la devise XOF.");

        return Command::SUCCESS;
    }
}