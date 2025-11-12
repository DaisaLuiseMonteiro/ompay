<?php

namespace Database\Factories;

use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement([
            Transaction::TYPE_PAIEMENT,
            Transaction::TYPE_VIREMENT,
            Transaction::TYPE_RETRAIT
        ]);

        $compte = Compte::factory()->create();
        $compteDestinataire = null;
        $montant = $this->faker->randomFloat(2, 1000, 1000000);
        $frais = $this->calculateFrais($type, $montant);

        if ($type === Transaction::TYPE_VIREMENT) {
            $compteDestinataire = Compte::factory()->create([
                'devise' => $compte->devise,
                'client_id' => $compte->client_id
            ]);
        }

        return [
            'reference' => 'TRX' . now()->format('YmdHis') . strtoupper(Str::random(6)),
            'type_transaction' => $type,
            'montant' => $montant,
            'frais' => $frais,
            'devise' => $compte->devise,
            'description' => $this->faker->sentence,
            'compte_id' => $compte->id,
            'compte_destinataire_id' => $compteDestinataire ? $compteDestinataire->id : null,
            'statut' => $this->faker->randomElement([
                Transaction::STATUT_VALIDEE,
                Transaction::STATUT_EN_ATTENTE,
                Transaction::STATUT_ANNULEE,
                Transaction::STATUT_ECHEC
            ]),
            'date_transaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    protected function calculateFrais(string $type, float $montant): float
    {
        return match($type) {
            Transaction::TYPE_PAIEMENT => $montant * 0.01, // 1% de frais
            Transaction::TYPE_VIREMENT => $montant * 0.005, // 0.5% de frais
            Transaction::TYPE_RETRAIT => 100, // Frais fixes pour les retraits
            default => 0,
        };
    }

    public function typePaiement()
    {
        return $this->state([
            'type_transaction' => Transaction::TYPE_PAIEMENT,
            'compte_destinataire_id' => null,
        ]);
    }

    public function typeVirement()
    {
        return $this->state([
            'type_transaction' => Transaction::TYPE_VIREMENT,
        ]);
    }

    public function typeRetrait()
    {
        return $this->state([
            'type_transaction' => Transaction::TYPE_RETRAIT,
            'compte_destinataire_id' => null,
        ]);
    }

    public function validee()
    {
        return $this->state(['statut' => Transaction::STATUT_VALIDEE]);
    }

    public function enAttente()
    {
        return $this->state(['statut' => Transaction::STATUT_EN_ATTENTE]);
    }

    public function annulee()
    {
        return $this->state(['statut' => Transaction::STATUT_ANNULEE]);
    }

    public function echec()
    {
        return $this->state(['statut' => Transaction::STATUT_ECHEC]);
    }
}
