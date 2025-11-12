<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $sexe = $this->faker->randomElement(['M', 'F']);
        $prenom = $sexe === 'M' ? $this->faker->firstNameMale : $this->faker->firstNameFemale;
        
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'nom' => $this->faker->lastName,
            'prenom' => $prenom,
            'sexe' => $sexe,
            'date_naissance' => $this->faker->dateTimeBetween('-70 years', '-18 years'),
            'adresse' => $this->faker->address,
            'telephone' => '7' . $this->faker->numerify('########'),
            'cni' => 'CI' . $this->faker->unique()->numerify('##########'),
            'statut' => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
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

    public function suspendu()
    {
        return $this->state(['statut' => 'suspendu']);
    }
}