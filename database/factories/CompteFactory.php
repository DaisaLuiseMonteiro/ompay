<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Compte;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompteFactory extends Factory
{
    protected $model = Compte::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'numero_compte' => 'CMPT' . now()->format('Ymd') . strtoupper(Str::random(6)),
            'solde_initial' => $this->faker->randomFloat(2, 0, 1000000),
            'devise' => $this->faker->randomElement(['XOF', 'EUR', 'USD']),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'bloque']),
            'date_ouverture' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
        ];
    }

    public function actif()
    {
        return $this->state(['statut' => 'actif']);
    }

    public function inactif()
    {
        return $this->state(['statut' => 'inactif']);
    }

    public function bloque()
    {
        return $this->state(['statut' => 'bloque']);
    }

    public function devise(string $devise)
    {
        return $this->state(['devise' => $devise]);
    }

    public function soldeInitial(float $montant)
    {
        return $this->state(['solde_initial' => $montant]);
    }
}
