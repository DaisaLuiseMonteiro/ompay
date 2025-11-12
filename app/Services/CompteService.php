<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\Transaction;

class CompteService
{
    /**
     * Calcule le solde actuel d'un compte
     * 
     * @param Compte $compte
     * @return float
     */
    public function calculerSolde(Compte $compte): float
    {
        // Paiements entrants
        $paiements = $compte->transactions()
            ->where('type_transaction', 'paiement')
            ->sum('montant');

        // Retraits sortants
        $retraits = $compte->transactions()
            ->where('type_transaction', 'retrait')
            ->sum('montant');

        // Virements sortants (débités du compte)
        $virements_sortants = $compte->transactions()
            ->where('type_transaction', 'virement')
            ->where('compte_id', $compte->id)
            ->sum('montant');

        // Virements entrants (crédités sur le compte)
        $virements_entrants = Transaction::where('type_transaction', 'virement')
            ->where('compte_destinataire_id', $compte->id)
            ->sum('montant');

        // Frais de transaction
        $frais = $compte->transactions()
            ->where('type_transaction', 'frais')
            ->sum('montant');

        // Calcul du solde total
        return $compte->solde_initial 
            + $paiements 
            - $retraits 
            - $virements_sortants 
            + $virements_entrants
            - $frais;
    }

    /**
     * Vérifie si le solde est suffisant pour un retrait
     * 
     * @param Compte $compte
     * @param float $montant
     * @return bool
     */
    public function soldeSuffisant(Compte $compte, float $montant): bool
    {
        return $this->calculerSolde($compte) >= $montant;
    }

    /**
     * Met à jour le solde d'un compte
     * 
     * @param Compte $compte
     * @param string $typeTransaction
     * @param float $montant
     * @param string|null $compteDestinataireId
     * @return bool
     */
    public function effectuerTransaction(
        Compte $compte, 
        string $typeTransaction, 
        float $montant, 
        ?string $compteDestinataireId = null
    ): bool {
        // Vérifier le solde pour les opérations débitrices
        if (in_array($typeTransaction, ['retrait', 'virement']) && 
            !$this->soldeSuffisant($compte, $montant)) {
            return false;
        }

        // Créer la transaction
        $transaction = new Transaction([
            'type_transaction' => $typeTransaction,
            'montant' => $montant,
            'compte_id' => $compte->id,
            'compte_destinataire_id' => $compteDestinataireId,
            'statut' => 'validee'
        ]);

        return $transaction->save();
    }
}
