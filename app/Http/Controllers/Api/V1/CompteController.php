<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\ApiResponse;
use App\Http\Resources\CompteResource;
use App\Models\Client;
use App\Models\Compte;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class CompteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un nouveau client et son compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "date_naissance", "telephone", "cni", "email", "sexe"},
     *             @OA\Property(property="nom", type="string", example="Doe"),
     *             @OA\Property(property="prenom", type="string", example="John"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="cni", type="string", example="1234567890123"),
     *             @OA\Property(property="sexe", type="string", enum={"M", "F"}, example="M")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Votre compte a été créé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides"),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type": "array", "items": {"type": "string"}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur lors de la création du compte")
     *         )
     *     )
     * )
     */
    public function creerCompte(Request $request)
    {
        // Log des données reçues
        Log::info('Données reçues : ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'telephone' => 'required|string|unique:clients,telephone',
            'email' => 'required|email|unique:clients,email',
            'cni' => 'required|string|unique:clients,cni',
            'sexe' => 'required|in:M,F',
            'code_secret' => 'required|string|min:4|max:4|regex:/^[0-9]+$/',
        ], [
            'email.required' => 'L\'adresse email est obligatoire',
            'email.email' => 'L\'adresse email n\'est pas valide',
            'email.unique' => 'Cette adresse email est déjà utilisée',
            'sexe.required' => 'Le sexe est obligatoire (M ou F)',
            'sexe.in' => 'Le sexe doit être M (Masculin) ou F (Féminin)',
            'code_secret.required' => 'Le code secret est obligatoire',
            'code_secret.min' => 'Le code secret doit contenir 4 chiffres',
            'code_secret.max' => 'Le code secret doit contenir 4 chiffres',
            'code_secret.regex' => 'Le code secret ne doit contenir que des chiffres',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                'Données invalides',
                400,
                $validator->errors()->toArray()
            );
        }

        try {
            DB::beginTransaction();

            $client = new Client([
                'id' => (string) Str::uuid(),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => Carbon::createFromFormat('Y-m-d', $request->date_naissance)->format('Y-m-d'),
                'telephone' => $request->telephone,
                'email' => $request->email,
                'cni' => $request->cni,
                'sexe' => $request->sexe,
                'statut' => 'actif',
                'email_verified_at' => now(),
            ]);
            $client->save();

            $compte = new Compte([
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'numero_compte' => $this->genererNumeroCompte(),
                'code_secret' => $request->code_secret,
                'solde_initial' => 0, // Solde initial à 0
                'devise' => 'XOF',
                'statut' => 'actif',
                'date_ouverture' => Carbon::now(),
            ]);
            $compte->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Votre compte a été créé avec succès'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création compte: ' . $e->getMessage());
            
            return ApiResponse::error(
                'Erreur lors de la création du compte: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/solde",
     *     summary="Consulter le solde du compte",
     *     description="Permet à un utilisateur authentifié de consulter le solde de son compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="C20231112345"),
     *                 @OA\Property(property="solde", type="number", format="float", example=50000.00),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="date_consultation", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun compte trouvé pour cet utilisateur")
     *         )
     *     )
     * )
     */
    /**
     * @OA\Get(
     *     path="/api/v1/comptes/solde",
     *     summary="Consulter le solde d'un compte spécifique",
     *     description="Permet à un utilisateur authentifié de consulter le solde d'un compte spécifique en fournissant son ID",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         required=true,
     *         description="ID du compte dont on veut consulter le solde",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="numero_compte", type="string", example="C20231112345"),
     *                 @OA\Property(property="solde", type="number", format="float", example=50000.00),
     *                 @OA\Property(property="devise", type="string", example="XOF"),
     *                 @OA\Property(property="date_consultation", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Solde récupéré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="L'ID du compte est requis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé à ce compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function consulterSolde(Request $request)
    {
        // Validation de la requête
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|uuid|exists:comptes,id',
        ], [
            'compte_id.required' => 'L\'ID du compte est requis',
            'compte_id.uuid' => 'Format d\'ID de compte invalide',
            'compte_id.exists' => 'Compte introuvable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 400);
        }

        // Récupération de l'utilisateur connecté
        $user = $request->user();
        
        // Vérification que l'utilisateur est un client
        if (!($user instanceof \App\Models\Client)) {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les clients peuvent consulter leur solde'
            ], 403);
        }
        
        // Récupération du compte avec vérification de propriété
        $compte = Compte::where('id', $request->compte_id)
                       ->where('client_id', $user->id)
                       ->first();
        
        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'numero_compte' => $compte->numero_compte,
                'solde' => (float) $compte->solde_initial,
                'devise' => $compte->devise ?? 'XOF',
                'date_consultation' => now()->toDateTimeString()
            ],
            'message' => 'Solde récupéré avec succès'
        ]);
    }

    /**
     * Génère un numéro de compte unique
     * 
     * @return string
     */
    private function genererNumeroCompte()
    {
        do {
            $numero = 'C' . date('Ym') . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $existe = Compte::where('numero_compte', $numero)->exists();
        } while ($existe);

        return $numero;
    }

     /**
     * @OA\Get(
     *     path="/api/v1/comptes/details",
     *     summary="Afficher les détails d'un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="query",
     *         required=true,
     *         description="Numéro de compte",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="compte", type="object", ref="#/components/schemas/Compte"),
     *                 @OA\Property(property="transactions", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Numéro de compte manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non autorisé"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé"
     *     )
     * )
     */
    public function afficherDetailsCompte(Request $request)
    {
    $request->validate([
        'numero_compte' => 'required|string|exists:comptes,numero_compte'
    ]);

    // Récupérer le compte avec le client associé
    $compte = Compte::with('client')
        ->where('numero_compte', $request->numero_compte)
        ->firstOrFail();

    // Vérifier que l'utilisateur est autorisé à voir ce compte
    $user = $request->user();
    if ($compte->client_id !== $user->id) {
        return response()->json([
            'success' => false,
            'message' => "Vous n'êtes pas autorisé à consulter ce compte"
        ], 403);
    }

    $transactions = $compte->transactions()
        ->latest('created_at')
        ->limit(10)
        ->get([
            'id', 
            'client_id',
            'compte_id',
            'reference', 
            'type', 
            'montant', 
            'statut', 
            'solde_avant',
            'solde_apres',
            'created_at as date_transaction'
        ]);

    // Formater les transactions pour inclure l'ID du client et de la transaction
    $transactionsFormatees = $transactions->map(function($transaction) use ($compte) {
        // Déterminer le signe du montant
        $signe = '';
        $montant = (float) $transaction->montant;
        
        // Logique de détermination du signe
        if ($transaction->type === 'virement') {
            // Pour les virements, on vérifie si c'est un envoi ou une réception
            // en fonction de la référence de la transaction
            // Si la référence se termine par -CR, c'est un crédit (+)
            $signe = (substr($transaction->reference, -3) === '-CR') ? '+' : '-';
        } 
        // Pour les transferts
        elseif ($transaction->type === 'transfert') {
            $signe = ($transaction->compte_id === $compte->id) ? '-' : '+';
        }
        // Pour les dépôts et réceptions, c'est un crédit (+)
        elseif (in_array($transaction->type, ['depot', 'reception'])) {
            $signe = '+';
        } 
        // Pour les retraits et paiements, c'est un débit (-)
        else {
            $signe = '-';
        }
        
        // Créer un tableau avec les champs de base
        $transactionData = [
            'id' => $transaction->id,
            'client_id' => $transaction->client_id,
            'reference' => $transaction->reference,
            'type' => $transaction->type,
            'montant' => $signe . number_format($montant, 2, '.', ' '),
            'statut' => $transaction->statut,
            'date_transaction' => $transaction->date_transaction
        ];

        // Ajouter les champs optionnels uniquement s'ils ne sont pas nuls
        $optionalFields = [
            'marchand',
            'deleted_at',
            'marchand_id',
            'compte_destinataire_id'
        ];

        foreach ($optionalFields as $field) {
            if (isset($transaction->$field) && $transaction->$field !== null) {
                $transactionData[$field] = $transaction->$field;
            }
        }

        return $transactionData;
    });

    return response()->json([
        'success' => true,
        'data' => [
            'compte' => [
                'id' => $compte->id,
                'numero_compte' => $compte->numero_compte,
                'solde' => (float) $compte->solde_initial,
                'devise' => $compte->devise ?? 'XOF',
                'date_ouverture' => $compte->date_ouverture
            ],
            'titulaire' => [
                'id' => $compte->client->id,
                'nom' => $compte->client->nom,
                'prenom' => $compte->client->prenom,
                'email' => $compte->client->email,
                'telephone' => $compte->client->telephone
            ],
            'transactions' => $transactionsFormatees
        ]
    ]);
}
}
