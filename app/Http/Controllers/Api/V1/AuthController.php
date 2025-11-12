<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Client;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Authentification", description="Endpoints pour l'authentification")
 */
class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Vérification des informations de connexion",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "email"},
     *             @OA\Property(property="telephone", type="string", example="770000000"),
     *             @OA\Property(property="email", type="string", format="email", example="client@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Un message vous a été envoyé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Données invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Données invalides")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si un client avec ce téléphone et cet email existe
        $client = Client::where('telephone', $request->telephone)
                      ->where('email', $request->email)
                      ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides'
            ], 401);
        }

        // Si l'utilisateur existe, on renvoie un message de succès
        return response()->json([
            'success' => true,
            'message' => 'Un message vous a été envoyé'
        ]);
    }
}