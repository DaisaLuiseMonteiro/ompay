<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use App\Models\Compte;
use App\Models\Marchand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Transactions",
 *     description="Opérations liées aux transactions"
 * )
 */
class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/transactions/historique",
     *     summary="Historique des transactions",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     *
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     summary="Afficher les détails d'une transaction",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la transaction",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(ref="#/components/schemas/Transaction")
     *     ),
     *     @OA\Response(response=404, description="Transaction non trouvée"),
     *     @OA\Response(response=401, description="Non authentifié")
     * )
     */
    /**
     * @OA\Post(
     *     path="/api/v1/transactions/transfert",
     *     summary="Effectuer un transfert d'argent",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_id", "numero_destinataire", "montant"},
     *             @OA\Property(property="compte_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID du compte émetteur"),
     *             @OA\Property(property="numero_destinataire", type="string", example="770000001", description="Numéro de téléphone du destinataire"),
     *             @OA\Property(property="montant", type="number", format="float", example=5000, description="Montant à transférer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transfert effectué avec succès"),
     *             @OA\Property(property="nouveau_solde", type="number", format="float", example=15000),
     *             @OA\Property(property="reference", type="string", example="TRX123456789"),
     *             @OA\Property(property="date", type="string", example="16/11/2025 11:30:45")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé ou solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solde insuffisant pour effectuer cette opération")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé")
     *         )
     *     )
     * )
     */
    public function effectuerTransfert(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|uuid|exists:comptes,id',
            'numero_destinataire' => 'required|string|exists:clients,telephone',
            'montant' => 'required|numeric|min:500|max:1000000',
        ], [
            'compte_id.required' => 'L\'ID du compte est requis',
            'compte_id.uuid' => 'Format d\'ID de compte invalide',
            'compte_id.exists' => 'Compte introuvable',
            'montant.min' => 'Le montant minimum pour un transfert est de 500 XOF',
            'montant.max' => 'Le montant maximum pour un transfert est de 1 000 000 XOF',
            'montant.numeric' => 'Le montant doit être un nombre',
            'numero_destinataire.exists' => 'Le numéro de téléphone du destinataire est invalide',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 400);
        }

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un client
            if (!($user instanceof \App\Models\Client)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les clients peuvent effectuer des transferts'
                ], 403);
            }
            
            // Récupérer le client
            $client = $user;
            
            // Vérifier que le compte appartient au client
            $expediteur = \App\Models\Compte::where('id', $request->compte_id)
                                        ->where('client_id', $client->id)
                                        ->first();
            
            if (!$expediteur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non trouvé ou accès non autorisé'
                ], 403);
            }

            // Vérifier que le destinataire n'est pas le même que l'expéditeur
            if ($client->telephone === $request->numero_destinataire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas vous envoyer de l\'argent à vous-même'
                ], 400);
            }
            
            // Trouver le client par son numéro de téléphone
            $clientDestinataire = \App\Models\Client::where('telephone', $request->numero_destinataire)->first();
            
            if (!$clientDestinataire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun destinataire trouvé avec ce numéro de téléphone'
                ], 404);
            }
            
            $destinataire = $clientDestinataire->compte;
            
            if (!$destinataire) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le destinataire n\'a pas de compte actif'
                ], 400);
            }
            
            // Calcul des frais (0.8% du montant)
            $frais = $request->montant * 0.008;
            $montantTotal = $request->montant + $frais;
            
            if ($expediteur->solde_initial < $montantTotal) {
                return response()->json([
                    'erreur' => 'Solde insuffisant pour effectuer le transfert. Montant total à débiter: ' . $montantTotal . ' XOF (Montant: ' . $request->montant . ' XOF, Frais: ' . $frais . ' XOF)'
                ], 403);
            }

            // Mise à jour des soldes
            $expediteur->decrement('solde_initial', $montantTotal);
            $destinataire->increment('solde_initial', $request->montant);
            
            // Rafraîchir les modèles pour obtenir les dernières valeurs
            $expediteur->refresh();
            $destinataire->refresh();
            
            $nouveauSolde = $expediteur->solde_initial;

            // Créer une référence unique
            $reference = 'TRX' . time() . strtoupper(Str::random(4));

            // Enregistrement de la transaction pour l'expéditeur (débit)
            $transactionExpediteur = Transaction::create([
                'compte_id' => $expediteur->id,
                'client_id' => $user->id,
                'type' => 'transfert',
                'montant' => $request->montant,
                'frais' => $frais,
                'solde_avant' => $expediteur->solde_initial + $montantTotal,
                'solde_apres' => $expediteur->solde_initial,
                'devise' => 'XOF',
                'statut' => 'terminee',
                'reference' => $reference,
                'date_transaction' => now(),
                'description' => 'Virement à ' . $clientDestinataire->prenom . ' ' . $clientDestinataire->nom,
            ]);

            // Enregistrement de la transaction pour le destinataire (crédit)
            $transactionDestinataire = Transaction::create([
                'compte_id' => $destinataire->id,
                'client_id' => $clientDestinataire->id,
                'type' => 'virement',
                'montant' => $request->montant,
                'frais' => 0,
                'solde_avant' => $destinataire->solde_initial - $request->montant,
                'solde_apres' => $destinataire->solde_initial,
                'devise' => 'XOF',
                'statut' => 'terminee',
                'reference' => $reference . '-CR',
                'date_transaction' => now(),
                'description' => 'Virement reçu de ' . $client->prenom . ' ' . $client->nom,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfert effectué avec succès',
                'nouveau_solde' => $nouveauSolde,
                'reference' => $reference,
                'date' => now()->format('d/m/Y H:i:s'),
                'transaction_id' => $transactionExpediteur->id,
                'destinataire' => [
                    'nom_complet' => $clientDestinataire->prenom . ' ' . $clientDestinataire->nom,
                    'montant_recu' => $request->montant,
                    'transaction_id' => $transactionDestinataire->id
                ]
            ]);
        });
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions/paiement",
     *     summary="Effectuer un paiement à un marchand",
     *     description="Effectue un paiement à un marchand en utilisant soit son code marchand, soit son numéro de téléphone",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_id", "montant"},
     *             @OA\Property(property="compte_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000", description="ID du compte émetteur"),
     *             @OA\Property(property="code_marchand", type="string", example="M123456", description="Code marchand (obligatoire si téléphone non fourni)"),
     *             @OA\Property(property="telephone", type="string", example="775312571", description="Numéro de téléphone du marchand (obligatoire si code_marchand non fourni)"),
     *             @OA\Property(property="montant", type="number", format="float", example=10000, description="Montant du paiement")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement effectué avec succès"),
     *             @OA\Property(property="nouveau_solde", type="number", format="float", example=5000),
     *             @OA\Property(property="marchand", type="object",
     *                 @OA\Property(property="nom", type="string", example="Nom du Marchand"),
     *                 @OA\Property(property="code_marchand", type="string", example="M123456")
     *             ),
     *             @OA\Property(property="reference", type="string", example="PAY123456789"),
     *             @OA\Property(property="date", type="string", example="16/11/2025 10:30:45")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé ou solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solde insuffisant pour effectuer cette opération"),
     *             @OA\Property(property="solde_disponible", type="number", example=5000),
     *             @OA\Property(property="montant_demande", type="number", example=10000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand ou compte introuvable",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun marchand trouvé avec ce code/numéro")
     *         )
     *     )
     * )
     */
    public function effectuerPaiement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|uuid|exists:comptes,id',
            'code_marchand' => 'required_without:telephone|string',
            'telephone' => 'required_without:code_marchand|string',
            'montant' => 'required|numeric|min:100',
        ], [
            'compte_id.required' => 'L\'ID du compte est requis',
            'compte_id.uuid' => 'Format d\'ID de compte invalide',
            'compte_id.exists' => 'Compte introuvable',
            'code_marchand.required_without' => 'Le code marchand est requis si le téléphone n\'est pas fourni',
            'telephone.required_without' => 'Le numéro de téléphone est requis si le code marchand n\'est pas fourni',
            'montant.required' => 'Le montant est requis',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant minimum est de 100 XOF',
        ]);

        // Vérification personnalisée pour s'assurer que code_marchand et telephone correspondent au même marchand
        if ($request->has('code_marchand') && $request->has('telephone')) {
            $validator->after(function ($validator) use ($request) {
                $telephone = preg_replace('/[^0-9]/', '', $request->telephone);
                $telephone = ltrim($telephone, '221');
                
                $marchandByCode = Marchand::where('code_marchand', $request->code_marchand)->first();
                $marchandByPhone = Marchand::whereRaw("REPLACE(REPLACE(telephone, ' ', ''), '+', '') = ?", [$telephone])->first();
                
                if (!$marchandByCode || !$marchandByPhone || $marchandByCode->id !== $marchandByPhone->id) {
                    $validator->errors()->add('inconsistent_merchant', 'Le code marchand et le numéro de téléphone ne correspondent pas au même marchand');
                }
            });
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 400);
        }

        return DB::transaction(function () use ($request) {
            $user = $request->user();
            
            // Vérifier que l'utilisateur est un client
            if (!($user instanceof \App\Models\Client)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les clients peuvent effectuer des paiements'
                ], 403);
            }
            
            // Récupérer le client
            $client = $user;
            
            // Vérifier que le compte appartient au client
            $compte = \App\Models\Compte::where('id', $request->compte_id)
                                       ->where('client_id', $client->id)
                                       ->first();
            
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non trouvé ou accès non autorisé'
                ], 403);
            }
            
            // Vérifier que le solde est suffisant
            if ($compte->solde_initial < $request->montant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant pour effectuer cette opération',
                    'solde_disponible' => $compte->solde_initial,
                    'montant_demande' => $request->montant
                ], 403);
            }
            
            // Récupérer le marchand par code_marchand ou par téléphone
            $marchand = null;
            if ($request->has('code_marchand')) {
                $marchand = Marchand::where('code_marchand', $request->code_marchand)->first();
                if (!$marchand) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun marchand trouvé avec ce code'
                    ], 404);
                }
            } else {
                // Nettoyer le numéro de téléphone
                $telephone = preg_replace('/[^0-9]/', '', $request->telephone);
                $telephone = ltrim($telephone, '221'); // Enlever l'indicatif 221 s'il existe
                
                // Recherche du marchand avec le numéro de téléphone nettoyé
                $marchand = Marchand::whereRaw("REPLACE(REPLACE(telephone, ' ', ''), '+', '') = ?", [$telephone])
                                  ->first();
                
                if (!$marchand) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Aucun marchand trouvé avec ce numéro de téléphone',
                        'telephone_recherche' => $telephone
                    ], 404);
                }
            }

            // Mettre à jour le solde du client (débit)
            $ancienSoldeClient = $compte->solde_initial;
            $compte->decrement('solde_initial', $request->montant);
            $compte->refresh();
            
            // Mettre à jour le solde du marchand (crédit)
            $ancienSoldeMarchand = $marchand->solde ?? 0;
            $marchand->increment('solde', $request->montant);
            $marchand->refresh();
            
            // Créer une référence unique
            $reference = 'PAY' . time() . strtoupper(Str::random(4));

            // Enregistrer la transaction de débit pour le client
            $transaction = Transaction::create([
                'compte_id' => $compte->id,
                'client_id' => $client->id,
                'marchand_id' => $marchand->id,
                'type' => 'paiement',
                'montant' => $request->montant,
                'frais' => 0,
                'solde_avant' => $ancienSoldeClient,
                'solde_apres' => $compte->solde_initial,
                'devise' => 'XOF',
                'statut' => 'terminee',
                'reference' => $reference . '-DEB',
                'date_transaction' => now(),
                'description' => 'Paiement à ' . $marchand->nom
            ]);

            // Enregistrer la transaction de crédit pour le marchand
            $transactionMarchand = Transaction::create([
                'compte_id' => null,
                'client_id' => null,
                'marchand_id' => $marchand->id,
                'type' => 'virement',
                'montant' => $request->montant,
                'frais' => 0,
                'solde_avant' => $ancienSoldeMarchand,
                'solde_apres' => $marchand->solde,
                'devise' => 'XOF',
                'statut' => 'terminee',
                'reference' => $reference . '-CRED',
                'date_transaction' => now(),
                'description' => 'Paiement reçu de ' . $client->prenom . ' ' . $client->nom
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectué avec succès',
                'nouveau_solde' => $compte->solde_initial,
                'marchand' => [
                    'nom' => $marchand->nom,
                    'code_marchand' => $marchand->code_marchand
                ],
                'reference' => $reference,
                'transaction_id' => $transaction->id,
                'date' => now()->format('d/m/Y H:i:s')
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/historique",
     *     summary="Historique des transactions du client",
     *     description="Récupère l'historique des transactions pour un compte spécifique",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         description="ID du compte pour lequel récupérer l'historique",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"paiement", "transfert", "depot", "virement"},
     *             example="paiement"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page pour la pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions paginées",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Transaction")
     *             ),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
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
     *     )
     * )
     */
    public function historique(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'compte_id' => 'required|uuid|exists:comptes,id'
        ], [
            'compte_id.required' => 'L\'ID du compte est requis',
            'compte_id.uuid' => 'Format d\'ID de compte invalide',
            'compte_id.exists' => 'Compte introuvable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $user = $request->user();
        
        // Vérification que l'utilisateur est un client
        if (!($user instanceof \App\Models\Client)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé. Seuls les clients peuvent consulter leur historique de transactions.'
            ], 403);
        }
        
        // Vérifier que le compte appartient bien au client connecté
        $compte = Compte::where('id', $request->compte_id)
                       ->where('client_id', $user->id)
                       ->first();

        if (!$compte) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non trouvé ou accès non autorisé'
            ], 403);
        }

        // Construire la requête pour récupérer les transactions
        $query = Transaction::where('compte_id', $compte->id)
            ->with([
                'compte' => function($q) {
                    $q->select('id', 'numero_compte', 'client_id')
                      ->with(['client' => function($q) {
                          $q->select('id', 'nom', 'prenom');
                      }]);
                },
                'marchand' => function($q) {
                    $q->select('id', 'nom', 'code_marchand');
                }
            ])
            ->orderBy('created_at', 'desc');

        // Filtrage par type de transaction si spécifié
        if ($request->has('type') && in_array($request->type, ['paiement', 'transfert', 'depot', 'virement'])) {
            $query->where('type', $request->type);
        }

        // Récupérer les transactions avec pagination
        $transactions = $query->paginate(10);

        // Formater les transactions
        $formattedTransactions = collect($transactions->items())->map(function($transaction) use ($compte) {
            // Créer une copie des données pour éviter de modifier l'objet original
            $data = $transaction->toArray();
            $montant = (float) $transaction->montant;
            $signe = '';

            // Logique de signe cohérente avec TransactionResource
            switch ($transaction->type) {
                case 'depot':
                case 'reception':
                case 'virement':
                    // Pour les dépôts, réceptions et virements, c'est un crédit (+)
                    $signe = '+';
                    break;
                    
                case 'retrait':
                case 'paiement':
                    // Pour les retraits et paiements, c'est un débit (-)
                    $signe = '-';
                    break;
                    
                case 'transfert':
                    // Pour les transferts, on vérifie si c'est un envoi ou une réception
                    // Si le solde a diminué (solde_apres < solde_avant), c'est un débit (-)
                    // Sinon, c'est un crédit (+)
                    $signe = ($transaction->solde_apres < $transaction->solde_avant) ? '-' : '+';
                    break;
                    
                default:
                    $signe = ''; // Par défaut, pas de signe
            }

            // Créer un tableau pour les données formatées avec les champs obligatoires
            $formattedData = [
                'id' => $data['id'],
                'reference' => $data['reference'],
                'type' => $data['type'],
                'montant' => $signe . number_format($montant, 2, '.', ' '),
                'frais' => $data['frais'],
                'devise' => $data['devise'],
                'description' => $data['description'],
                'compte_id' => $data['compte_id'],
                'statut' => $data['statut'],
                'date_transaction' => $data['date_transaction'],
                'client_id' => $data['client_id'],
                'solde_avant' => $data['solde_avant'],
                'solde_apres' => $data['solde_apres'],
                'montant_numerique' => $montant
            ];

            // Ajouter les champs optionnels uniquement s'ils ne sont pas nuls
            $optionalFields = [
                'compte_destinataire_id',
                'created_at',
                'updated_at',
                'deleted_at',
                'marchand_id',
                'compte',
                'marchand'
            ];

            foreach ($optionalFields as $field) {
                if (isset($data[$field]) && $data[$field] !== null) {
                    $formattedData[$field] = $data[$field];
                }
            }
            
            return $formattedData;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedTransactions,
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{reference}",
     *     summary="Afficher les détails d'une transaction par sa référence",
     *     description="Récupère les détails d'une transaction en utilisant sa référence unique",
     *     tags={"Transactions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         required=true,
     *         description="Référence unique de la transaction",
     *         @OA\Schema(type="string", example="PAY123456789")
     *     ),
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         description="ID du compte associé à la transaction",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
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
     *         response=404,
     *         description="Transaction non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Transaction non trouvée")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé à cette transaction")
     *         )
     *     )
     * )
     */
    public function show(Request $request, $reference)
    {
        try {
            $validator = Validator::make($request->all(), [
                'compte_id' => 'required|uuid|exists:comptes,id'
            ], [
                'compte_id.required' => 'L\'ID du compte est requis',
                'compte_id.uuid' => 'Format d\'ID de compte invalide',
                'compte_id.exists' => 'Compte introuvable'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $user = $request->user();
            
            // Vérifier que l'utilisateur est un client
            if (!($user instanceof \App\Models\Client)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé. Seuls les clients peuvent consulter leurs transactions.'
                ], 403);
            }
            
            // Vérifier que le compte appartient à l'utilisateur
            $compte = Compte::where('id', $request->compte_id)
                          ->where('client_id', $user->id)
                          ->first();
            
            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non trouvé ou accès non autorisé'
                ], 403);
            }

            // Récupérer la transaction pour le compte spécifié
            $transaction = Transaction::where('reference', $reference)
                                   ->where('compte_id', $compte->id)
                                   ->with([
                                       'compte' => function($q) {
                                           $q->select('id', 'numero_compte', 'client_id')
                                             ->with(['client' => function($q) {
                                                 $q->select('id', 'nom', 'prenom');
                                             }]);
                                       },
                                       'marchand' => function($q) {
                                           $q->select('id', 'nom', 'code_marchand');
                                       }
                                   ])
                                   ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée pour ce compte'
                ], 404);
            }

            // Ajouter le compte_id à la requête pour qu'il soit disponible dans TransactionResource
            $request->merge(['compte_id' => $compte->id]);
            
            return response()->json([
                'success' => true,
                'data' => new \App\Http\Resources\TransactionResource($transaction)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération de la transaction',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}