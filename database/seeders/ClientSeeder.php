<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création de 10 clients actifs
        $sexes = ['M', 'F'];
        
        for ($i = 1; $i <= 10; $i++) {
            Client::factory()
                ->actif()
                ->create([
                    'sexe' => $sexes[array_rand($sexes)],
                    'cni' => 'CI' . str_pad($i, 8, '0', STR_PAD_LEFT) . 
                             $sexes[array_rand($sexes)] . 
                             str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT)
                ]);
        }
    }
}
