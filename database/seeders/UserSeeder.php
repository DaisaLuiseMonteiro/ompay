<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur par défaut
        User::firstOrCreate(
            ['email' => 'admin@om-pay.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Administrateur',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'statut' => 'actif',
                'telephone' => '771234567',
                'email_verified_at' => now(),
            ]
        );

        // Créer un utilisateur client avec un autre numéro
        User::firstOrCreate(
            ['email' => 'client@om-pay.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Client Test',
                'password' => Hash::make('password'),
                'role' => 'client',
                'statut' => 'actif',
                'telephone' => '772345678',
                'email_verified_at' => now(),
            ]
        );

        // Créer un utilisateur client inactif
        User::firstOrCreate(
            ['email' => 'inactif@om-pay.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Utilisateur Inactif',
                'password' => Hash::make('password'),
                'role' => 'client',
                'statut' => 'inactif',
                'telephone' => '773456789',
                'email_verified_at' => now(),
            ]
        );

        // Créer un utilisateur marchand
        User::firstOrCreate(
            ['email' => 'marchand@om-pay.com'],
            [
                'id' => Str::uuid(),
                'name' => 'Marchand Test',
                'password' => Hash::make('password'),
                'role' => 'marchand',
                'statut' => 'actif',
                'telephone' => '774567890',
                'email_verified_at' => now(),
            ]
        );
    }
}
