<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarchandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marchands = [
            [
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'sexe' => 'M',
                'telephone' => '771234567',
                'code_marchand' => 'M' . date('Ymd') . '01',
                'nom_commerce' => 'Boutique Fashion',
                'statut' => 'actif',
                'solde' => 0, // Solde initialisé à 0
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'sexe' => 'F',
                'telephone' => '772345678',
                'code_marchand' => 'M' . date('Ymd') . '02',
                'nom_commerce' => 'Restaurant Le Bon Goût',
                'statut' => 'actif',
                'solde' => 0, // Solde initialisé à 0
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Dubois',
                'prenom' => 'Pierre',
                'sexe' => 'M',
                'telephone' => '773456789',
                'code_marchand' => 'M' . date('Ymd') . '03',
                'nom_commerce' => 'Super Marché Express',
                'statut' => 'actif',
                'solde' => 0, // Solde initialisé à 0
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Bernard',
                'prenom' => 'Marie',
                'sexe' => 'F',
                'telephone' => '774567890',
                'code_marchand' => 'M' . date('Ymd') . '04',
                'nom_commerce' => 'Librairie L\'Écume des Pages',
                'statut' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Petit',
                'prenom' => 'Luc',
                'sexe' => 'M',
                'telephone' => '775678901',
                'code_marchand' => 'M' . date('Ymd') . '05',
                'nom_commerce' => 'Électro-Ménager Plus',
                'statut' => 'actif',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($marchands as $marchand) {
            DB::table('marchands')->updateOrInsert(
                ['code_marchand' => $marchand['code_marchand']],
                $marchand
            );
        }
    }
}
