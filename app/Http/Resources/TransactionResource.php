<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $montant = (float) $this->montant;
        $signe = '';

        // Déterminer le signe en fonction du type de transaction
        if ($this->type === 'virement') {
            // Pour les virements, on vérifie si c'est un envoi ou une réception
            // Si le solde a augmenté (solde_apres > solde_avant), c'est un crédit (+)
            // Sinon, c'est un débit (-)
            $signe = ($this->solde_apres > $this->solde_avant) ? '+' : '-';
        } elseif ($this->type === 'transfert') {
            // Pour les transferts, on vérifie si c'est un envoi ou une réception
            // Si le solde a diminué (solde_apres < solde_avant), c'est un débit (-)
            // Sinon, c'est un crédit (+)
            $signe = ($this->solde_apres < $this->solde_avant) ? '-' : '+';
        } elseif (in_array($this->type, ['depot', 'reception'])) {
            // Pour les dépôts et réceptions, c'est toujours un crédit
            $signe = '+';
        } else {
            // Pour les retraits et paiements, c'est toujours un débit
            $signe = '-';
        }

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'type' => $this->type,
            'montant' => $signe . number_format($montant, 2, '.', ' '),
            'montant_numerique' => $signe . $montant,
            'frais' => $this->frais,
            'devise' => $this->devise,
            'description' => $this->description,
            'statut' => $this->statut,
            'date_transaction' => $this->date_transaction,
            'solde_avant' => $this->solde_avant,
            'solde_apres' => $this->solde_apres,
        ];
    }
}
