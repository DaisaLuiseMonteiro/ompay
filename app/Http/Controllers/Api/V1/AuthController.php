<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller as BaseController;
use Illuminate\Support\Str;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;



/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification des utilisateurs"
 * )
 *

*/

class AuthController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Génère un code OTP",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone","email"},
     *             @OA\Property(property="telephone", type="string", example="770000000"),
     *             @OA\Property(property="email", type="string", format="email", example="client@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code OTP généré et envoyé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="otp", type="string", example="123456"),
     *             @OA\Property(property="expires_in", type="integer", example=300)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
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

    // Nettoyer le numéro de téléphone (enlever +221 et espaces)
    $telephone = preg_replace('/[^0-9]/', '', $request->telephone);
    $telephone = str_replace('221', '', $telephone); // Enlève l'indicatif 221 s'il est présent
    
    // Chercher le client par email d'abord
    $client = Client::where('email', $request->email)->first();
    
    if (!$client) {
        return response()->json([
            'success' => false,
            'message' => 'Aucun compte trouvé avec cet email.'
        ], 401);
    }
    
    // Vérifier que le numéro de téléphone correspond (en ignorant l'indicatif)
    $clientPhone = preg_replace('/[^0-9]/', '', $client->telephone);
    $clientPhone = str_replace('221', '', $clientPhone);
    
    if ($clientPhone !== $telephone) {
        return response()->json([
            'success' => false,
            'message' => 'Le numéro de téléphone ne correspond pas à ce compte.'
        ], 401);
    }

    try {
        $otp = $client->generateNewOtp('email');
        
        // Log de débogage
        \Log::info('Nouvel OTP généré', [
            'client_id' => $client->id,
            'email' => $client->email,
            'otp_code' => $otp,
            'otp_expires_at' => $client->otp_expires_at,
            'heure' => now()->toDateTimeString()
        ]);
        
        // Envoyer le code OTP par email
        $client->notify(new \App\Notifications\OtpNotification($otp, 'email'));
        
        // Vérifier que l'OTP est bien enregistré en base de données
        $clientFromDb = Client::find($client->id);
        \Log::info('Vérification de l\'OTP en base de données', [
            'otp_en_base' => $clientFromDb->otp_code,
            'expiration_en_base' => $clientFromDb->otp_expires_at,
            'heure_verification' => now()->toDateTimeString()
        ]);
        
        \Log::info('OTP généré et envoyé', [
            'client_id' => $client->id,
            'email' => $client->email,
            'otp' => $otp
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Code OTP envoyé avec succès',
            'expires_in' => 300
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur lors de la génération du code OTP: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Une erreur est survenue lors de la génération du code OTP.'
        ], 500);
    }
}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/verify-otp",
     *     summary="Vérifie le code OTP et authentifie l'utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="123456", description="Code OTP à 6 chiffres")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Code OTP invalide ou expiré"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Journalisation pour le débogage
            \Log::info('Tentative de vérification OTP', [
                'code_fourni' => $request->code,
                'heure' => now()->toDateTimeString()
            ]);

            // Récupérer le client avec le code OTP non expiré
            $client = Client::where('otp_code', $request->code)
                          ->where('otp_expires_at', '>', now())
                          ->first();

            // Log de débogage
            \Log::info('Recherche du client avec OTP', [
                'code_recherche' => $request->code,
                'clients_trouves' => Client::where('otp_code', $request->code)->count(),
                'heure_actuelle' => now()->toDateTimeString(),
                'client_trouve' => $client ? true : false
            ]);

            if (!$client) {
                // Vérifier si le code existe mais est expiré
                $expiredClient = Client::where('otp_code', $request->code)
                                    ->where('otp_expires_at', '<=', now())
                                    ->first();

                \Log::warning('OTP non trouvé ou expiré', [
                    'code' => $request->code,
                    'heure' => now()->toDateTimeString(),
                    'est_expire' => $expiredClient ? true : false,
                    'date_expiration' => $expiredClient ? $expiredClient->otp_expires_at : null
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Code OTP invalide ou expiré.'
                ], 400);
            }

            try {
                // Invalider l'OTP de manière asynchrone
                dispatch(function() use ($client) {
                    $client->invalidateOtp();
                })->afterResponse();

                // Créer un nouveau token d'accès
                $tokenResult = $client->createToken('Personal Access Token');
                $token = $tokenResult->token;
                $token->expires_at = now()->addDays(30); // Expire dans 30 jours
                $token->save();

                // Créer un refresh token
                $refreshToken = Str::random(80);
                
                // Mettre à jour l'utilisateur avec le nouveau refresh token
                $client->update([
                    'refresh_token' => hash('sha256', $refreshToken),
                    'refresh_token_expires_at' => now()->addDays(30)
                ]);

                // Journalisation
                \Log::info('Nouveau token créé', [
                    'client_id' => $client->id,
                    'token_id' => $token->id,
                    'expires_at' => $token->expires_at
                ]);

                // Créer la réponse avec le refresh token inclus
                $response = response()->json([
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'access_token' => $tokenResult->accessToken,
                    'refresh_token' => $refreshToken, // Ajout du refresh token dans la réponse
                    'token_type' => 'Bearer',
                    'expires_in' => $token->expires_at->diffInSeconds(now())
                ]);

                // Ajouter également le refresh token dans un cookie HTTP Only pour une sécurité renforcée
                return $response->cookie(
                    'refresh_token',
                    $refreshToken,
                    60 * 24 * 30, // 30 jours
                    '/',
                    null,
                    config('app.env') === 'production',
                    true, // HTTP Only
                    false,
                    'lax'
                );

            } catch (\Exception $e) {
                \Log::error('Erreur lors de la création du jeton d\'accès: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la création du jeton d\'accès.'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Erreur lors de la vérification OTP: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la vérification du code OTP.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh-token",
     *     summary="Rafraîchir le token d'accès",
     *     description="Rafraîchit le token d'accès en utilisant le refresh token (depuis les cookies ou le corps de la requête)",
     *     tags={"Authentification"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         description="Refresh token (optionnel si fourni dans les cookies)",
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", description="Le refresh token (si non fourni dans les cookies)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string", description="Nouveau token d'accès"),
     *             @OA\Property(property="refresh_token", type="string", description="Nouveau refresh token (à conserver pour les requêtes suivantes)"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes")
     *         ),
     *         @OA\Header(
     *             header="Set-Cookie",
     *             description="Contient le nouveau refresh token dans un cookie HTTP Only (si utilisé avec un navigateur)",
     *             @OA\Schema(type="string", example="refresh_token=abc123...; HttpOnly; Path=/; Max-Age=2592000; SameSite=lax")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié ou refresh token invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Refresh token manquant ou invalide")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Impossible de rafraîchir le token.")
     *         )
     *     )
     * )
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = $request->user();
            
            // Récupérer le refresh token depuis les cookies ou le corps de la requête
            $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');
            
            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token manquant. Fournissez-le soit dans un cookie HTTP Only, soit dans le corps de la requête.'
                ], 401);
            }
            
            // Créer un nouveau token d'accès
            $token = $user->createToken('Personal Access Token');
            
            // Créer un nouveau refresh token
            $newRefreshToken = Str::random(80);
            
            // Stocker le refresh token dans la base de données
            $user->update([
                'refresh_token' => hash('sha256', $newRefreshToken),
                'refresh_token_expires_at' => now()->addDays(30)
            ]);
            
            // Créer la réponse avec le token d'accès et le nouveau refresh token
            $responseData = [
                'access_token' => $token->accessToken,
                'refresh_token' => $newRefreshToken, // Inclure le nouveau refresh token dans la réponse
                'token_type' => 'Bearer',
                'expires_in' => $token->token->expires_at->diffInSeconds(now())
            ];
            
            // Créer la réponse JSON
            $response = response()->json($responseData);
            
            // Si la requête vient d'un navigateur (avec cookies), on ajoute le refresh token dans un cookie HTTP Only
            if ($request->hasCookie('refresh_token')) {
                return $response->cookie(
                    'refresh_token',
                    $newRefreshToken,
                    60 * 24 * 30, // 30 jours
                    '/',
                    null,
                    config('app.env') === 'production',
                    true, // HTTP Only
                    false,
                    'lax'
                );
            }
            
            return $response;
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors du rafraîchissement du token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Impossible de rafraîchir le token.'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Déconnexion de l'utilisateur",
     *     tags={"Authentification"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compte_id",
     *         in="query",
     *         required=true,
     *         description="ID du compte à déconnecter (format UUID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID de compte manquant ou invalide"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé à ce compte"
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            // Valider que le compte_id est présent et est un UUID valide
            $validated = $request->validate([
                'compte_id' => 'required|string|uuid|exists:comptes,id'
            ]);
            
            $user = $request->user();
            $compteId = $validated['compte_id'];
            
            // Vérifier que l'utilisateur a bien accès à ce compte
            $compte = Compte::find($compteId);
            
            if (!$compte || $compte->client_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accès non autorisé à ce compte'
                ], 403);
            }
            
            // Supprimer le refresh token de la base de données
            $user->update([
                'refresh_token' => null,
                'refresh_token_expires_at' => null
            ]);
            
            // Révoquer le token d'accès actuel
            $request->user()->token()->revoke();
            
            // Révoquer tous les tokens de l'utilisateur
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
            
            \Log::info('Utilisateur déconnecté', [
                'user_id' => $user->id,
                'email' => $user->email,
                'compte_id' => $compteId
            ]);
            
            // Créer la réponse avec le cookie de déconnexion
            $response = response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
            
            // Supprimer le cookie de refresh token en le rendant expiré
            return $response->cookie(
                'refresh_token',
                '',
                -1, // Date d'expiration dans le passé
                '/',
                null,
                config('app.env') === 'production',
                true, // HTTP Only
                false,
                'lax'
            );
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la déconnexion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la déconnexion.'
            ], 500);
        }
    }
}
