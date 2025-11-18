<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OtpVerificationController extends Controller
{
    /**
     * Vérifie le code OTP
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string|max:20',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Rechercher le client par téléphone
        $client = Client::where('telephone', $request->telephone)->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun client trouvé avec ce numéro de téléphone.'
            ], 404);
        }

        // Vérifier si le code OTP est valide
        if ($client->isValidOtp($request->code)) {
            // Invalider le code après utilisation
            $client->invalidateOtp();

            // Ici, vous pouvez ajouter la logique de connexion ou de génération de token
            // Par exemple :
            // $token = $client->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Code OTP vérifié avec succès',
                // 'token' => $token,
                'client' => $client->only(['id', 'prenom', 'nom', 'email', 'telephone'])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Code OTP invalide ou expiré.'
        ], 400);
    }
}
