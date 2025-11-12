<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

class TransactionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/transactions/transfert",
     *     summary="Effectuer un transfert d'argent",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero_destinataire", "montant"},
     *             @OA\Property(property="numero_destinataire", type="string", example="770000001"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="nouveau_solde", type="number", format="float", example=15000)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Compte destinataire introuvable"),
     *     @OA\Response(response=403, description="Solde insuffisant")
     * )
     */
    public function effectuerTransfert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'numero_destinataire' => 'required|string|exists:comptes,numero_compte',
            'montant' => 'required|numeric|min:100|max:1000000',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        return DB::transaction(function () use ($request) {
            $expediteur = $request->user()->compte;
            $destinataire = Compte::where('numero_compte', $request->numero_destinataire)->firstOrFail();
            
            if ($expediteur->solde < $request->montant) {
                return response()->json(['message' => 'Solde insuffisant'], 403);
            }

            // Effectuer le transfert
            $expediteur->decrement('solde', $request->montant);
            $destinataire->increment('solde', $request->montant);

            // Enregistrer la transaction
            Transaction::create([
                'compte_id' => $expediteur->id,
                'type' => 'transfert',
                'montant' => $request->montant,
                'destinataire_id' => $destinataire->client_id,
                'statut' => 'termine',
                'frais' => 0,
                'reference' => 'TRX' . time(),
                'solde_apres' => $expediteur->solde - $request->montant
            ]);

            return response()->json([
                'message' => 'Transfert effectué avec succès',
                'nouveau_solde' => $expediteur->fresh()->solde
            ]);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions/paiement",
     *     summary="Effectuer un paiement à un marchand",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"marchand_id", "montant"},
     *             @OA\Property(property="marchand_id", type="integer", example=1),
     *             @OA\Property(property="montant", type="number", format="float", example=10000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="nouveau_solde", type="number", format="float", example=5000)
     *         )
     *     ),
     *     @OA\Response(response=400, description="Données invalides"),
     *     @OA\Response(response=404, description="Marchand introuvable"),
     *     @OA\Response(response=403, description="Solde insuffisant")
     * )
     */
    public function effectuerPaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'marchand_id' => 'required|exists:marchands,id',
            'montant' => 'required|numeric|min:100',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        return DB::transaction(function () use ($request) {
            $client = $request->user();
            $compte = $client->compte;
            
            if ($compte->solde < $request->montant) {
                return response()->json(['message' => 'Solde insuffisant'], 403);
            }

            // Effectuer le paiement
            $compte->decrement('solde', $request->montant);

            // Enregistrer la transaction
            Transaction::create([
                'compte_id' => $compte->id,
                'type' => 'paiement',
                'montant' => $request->montant,
                'destinataire_id' => $request->marchand_id,
                'statut' => 'termine',
                'frais' => 0,
                'reference' => 'PAY' . time(),
                'solde_apres' => $compte->solde - $request->montant
            ]);

            return response()->json([
                'message' => 'Paiement effectué avec succès',
                'nouveau_solde' => $compte->fresh()->solde
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/historique",
     *     summary="Historique des transactions du client",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Transaction")
     *         )
     *     )
     * )
     */
    public function historique(Request $request)
    {
        $transactions = $request->user()->compte->transactions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($transactions);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Afficher les détails d'une transaction par son ID",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non autorisé"),
     *     @OA\Response(response=403, description="Accès non autorisé")
     * )
     */
    public function show($id)
    {
        $transaction = Transaction::with(['compte', 'client', 'marchand'])->findOrFail($id);
        
        // Vérifier que l'utilisateur a le droit de voir cette transaction
        if ($transaction->compte_id !== auth()->user()->compte->id) {
            return response()->json(['message' => 'Accès non autorisé à cette transaction'], 403);
        }
        
        return response()->json($transaction);
    }
}