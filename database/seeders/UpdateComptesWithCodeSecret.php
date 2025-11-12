<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;

class UpdateComptesWithCodeSecret extends Seeder
{
    public function run()
    {
        $comptes = Compte::whereNull('code_secret')->get();
        
        foreach ($comptes as $compte) {
            $compte->update([
                'code_secret' => str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT)
            ]);
        }
        
        $this->command->info('Code secret ajouté à ' . $comptes->count() . ' comptes.');
    }
}
