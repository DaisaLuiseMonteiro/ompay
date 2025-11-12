<?php

namespace Database\Factories;

use App\Models\Marchand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MarchandFactory extends Factory
{
    protected $model = Marchand::class;

    public function definition(): array
    {
        $gender = $this->faker->randomElement(['M', 'F']);
        $firstName = $gender === 'M' ? $this->faker->firstNameMale : $this->faker->firstNameFemale;
        
        return [
            'nom' => $this->faker->lastName,
            'prenom' => $firstName,
            'sexe' => $gender,
            'telephone' => '3' . $this->faker->numerify('########'),
            'code_marchand' => 'M' . $this->faker->unique()->randomNumber(6, true),
            'nom_commerce' => $this->faker->company,
            'statut' => $this->faker->randomElement(['actif', 'inactif']),
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
}
