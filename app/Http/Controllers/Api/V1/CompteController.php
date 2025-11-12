<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Compte;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ClientInput",
 *     type="object",
 *     required={"nom", "prenom", "date_naissance", "cni", "telephone", "solde_initial"},
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenom", type="string", example="John"),
 *     @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="adresse", type="string", nullable=true, example="123 Rue Exemple"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="cni", type="string", example="1234567890123"),
 *     @OA\Property(property="solde_initial", type="number", format="float", example=10000)
 * )
 */

/**
 * @OA\Schema(
 *     schema="CompteResponse",
 *     type="object",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client"),
 *     @OA\Property(property="compte", ref="#/components/schemas/Compte")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     required={"success", "message"},
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message")
 * )
 * 
 * @OA\Schema(
 *     schema="Success",
 *     type="object",
 *     required={"success", "message"},
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success message")
 * )
 */

class CompteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/comptes/creer",
     *     summary="Créer un nouveau client et son compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ClientInput")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client et compte créés avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/CompteResponse")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Données invalides",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la création",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function creerCompte(Request $request)
    {
        // Validation des données
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'required|string|unique:clients,telephone',
            'cni' => 'required|string|unique:clients,cni',
            'solde_initial' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Démarrer une transaction
            DB::beginTransaction();

            // Générer un code secret aléatoire à 4 chiffres
            $codeSecret = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            // Créer le client
            $client = new Client([
                'id' => (string) Str::uuid(),
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'date_naissance' => $request->date_naissance,
                'adresse' => $request->adresse,
                'telephone' => $request->telephone,
                'cni' => $request->cni,
                'code_secret' => $codeSecret,
                'statut' => 'actif',
            ]);
            $client->save();

            // Générer un numéro de compte unique
            $numeroCompte = $this->genererNumeroCompte();

            // Créer le compte
            $compte = new Compte([
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'numero_compte' => $numeroCompte,
                'solde_initial' => $request->solde_initial,
                'solde' => $request->solde_initial,
                'devise' => 'XOF',
                'statut' => 'actif',
                'date_ouverture' => Carbon::now(),
            ]);
            $compte->save();

            // Valider la transaction
            DB::commit();

            return response()->json([
                'message' => 'Client et compte créés avec succès',
                'client' => $client,
                'compte' => $compte
            ], 201);

        } catch (\Exception $e) {
            // En cas d'erreur, annuler la transaction
            DB::rollBack();
            \Log::error('Erreur lors de la création du client et du compte: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création du client et du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Génère un numéro de compte unique
     * 
     * @return string
     */
    private function genererNumeroCompte()
    {
        do {
            // Format: C + Année + Mois + 5 chiffres aléatoires
            $numero = 'C' . date('Ym') . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $existe = Compte::where('numero_compte', $numero)->exists();
        } while ($existe);

        return $numero;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/solde",
     *     summary="Consulter le solde du compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="solde", type="number", format="float", example=50000),
     *             @OA\Property(property="devise", type="string", example="XOF")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Compte non trouvé")
     * )
     */
    public function consulterSolde(Request $request)
    {
        $compte = $request->user()->compte;
        
        if (!$compte) {
            return response()->json(['message' => 'Compte non trouvé'], 404);
        }

        return response()->json([
            'solde' => $compte->solde,
            'devise' => 'XOF'
        ]);
    }
}
