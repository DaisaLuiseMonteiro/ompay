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
            'telephone' => '7' . $this->faker->unique()->numerify('########'),
            'email' => $this->faker->unique()->safeEmail,
            'cni' => 'CI' . $this->faker->unique()->numerify('##########'),
            'statut' => 'actif',
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_type' => null,
            'password' => bcrypt('password'), // Default password
            'created_at' => now(),
            'updated_at' => now(),
            'email_verified_at' => now(),
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

    public function withOtp(string $type = 'sms')
    {
        return $this->state([
            'otp_code' => (string) $this->faker->randomNumber(6, true),
            'otp_expires_at' => now()->addMinutes(30),
            'otp_type' => $type,
        ]);
    }

    public function withExpiredOtp(string $type = 'sms')
    {
        return $this->state([
            'otp_code' => (string) $this->faker->randomNumber(6, true),
            'otp_expires_at' => now()->subMinutes(5),
            'otp_type' => $type,
        ]);
    }
}