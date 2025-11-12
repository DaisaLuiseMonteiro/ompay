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
     *     path="/api/v1/comptes/creer",
     *     summary="Créer un nouveau client et son compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom", "prenom", "date_naissance", "telephone", "cni", "solde_initial"},
     *             @OA\Property(property="nom", type="string", example="Doe"),
     *             @OA\Property(property="prenom", type="string", example="John"),
     *             @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="adresse", type="string", nullable=true, example="123 Rue Exemple"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="cni", type="string", example="1234567890123"),
     *             @OA\Property(property="solde_initial", type="number", format="float", example=10000)
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
            return ApiResponse::error(
                'Données invalides',
                400,
                $validator->errors()->toArray()
            );
        }

        try {
            DB::beginTransaction();

            $codeSecret = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

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

            $compte = new Compte([
                'id' => (string) Str::uuid(),
                'client_id' => $client->id,
                'numero_compte' => $this->genererNumeroCompte(),
                'solde_initial' => $request->solde_initial,
                'solde' => $request->solde_initial,
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
     *     tags={"Comptes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Solde du compte",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object", 
     *                 @OA\Property(property="solde", type="number", format="float", example=1000.50),
     *                 @OA\Property(property="devise", type="string", example="XOF")
     *             ),
     *             @OA\Property(property="success", type="boolean", example=true),
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
     *     )
     * )
     */
    public function consulterSolde(Request $request)
    {
        $compte = $request->user()->compte;
        
        return (new ApiResponse([
            'solde' => $compte->solde,
            'devise' => $compte->devise
        ], 'Solde récupéré avec succès'))->response();
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
}
