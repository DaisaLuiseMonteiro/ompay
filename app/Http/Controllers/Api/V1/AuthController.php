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
     *     summary="Connexion d'un utilisateur",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"telephone", "code_pin"},
     *             @OA\Property(property="telephone", type="string", example="770000000"),
     *             @OA\Property(property="code_pin", type="string", format="password", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Identifiants invalides"),
     *     @OA\Response(response=422, description="Erreur de validation")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string',
            'code_pin' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only(['telephone', 'code_pin']);
        
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60
        ]);
    }
}