<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller as BaseController;
use App\Models\Client;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Authentification", description="Endpoints pour l'authentification")
 */
class AuthController extends BaseController
{
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

        $client = Client::where('telephone', $request->telephone)
                      ->where('email', $request->email)
                      ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides'
            ], 401);
        }

        // Générer un code OTP à 6 chiffres
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Mettre à jour le client avec le nouvel OTP et sa date d'expiration
        $client->update([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(10) // OTP valable 10 minutes
        ]);

        // Envoyer le code par SMS via Twilio
        if (config('services.twilio.enabled')) {
            try {
                // Formater le numéro pour Twilio (ajouter l'indicatif du pays si nécessaire)
                $phoneNumber = $client->telephone;
                
                // Nettoyer le numéro de téléphone
                $phoneNumber = preg_replace('/[^0-9]+/', '', $phoneNumber);
                
                // Si le numéro commence par 0, on le remplace par +221 (pour le Sénégal)
                if (str_starts_with($phoneNumber, '0')) {
                    $phoneNumber = '+221' . substr($phoneNumber, 1);
                }
                // Si le numéro commence par 221, on ajoute juste le +
                elseif (str_starts_with($phoneNumber, '221')) {
                    $phoneNumber = '+' . $phoneNumber;
                }
                // Si le numéro commence par 7 ou 77, on ajoute +221
                elseif (preg_match('/^7[0-9]/', $phoneNumber)) {
                    $phoneNumber = '+221' . $phoneNumber;
                }
                
                // Vérifier que le numéro est au bon format pour Twilio
                if (!preg_match('/^\+[1-9]\d{1,14}$/', $phoneNumber)) {
                    \Log::error('Format de numéro de téléphone invalide pour Twilio', [
                        'original' => $client->telephone,
                        'formaté' => $phoneNumber
                    ]);
                } else {
                    $client->notify(new OtpNotification($otp, 'sms'));
                }
            } catch (\Exception $e) {
                \Log::error('Erreur lors de l\'envoi du SMS', [
                    'error' => $e->getMessage(),
                    'phone' => $client->telephone
                ]);
            }
        }

        // Envoyer le code par email
        try {
            $client->notify(new OtpNotification($otp, 'email'));
        } catch (\Exception $e) {
            \Log::error('Erreur lors de l\'envoi de l\'email', [
                'error' => $e->getMessage(),
                'email' => $client->email
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Un code de vérification a été généré. Vérifiez votre téléphone et/ou email.'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string',
            'code' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::where('telephone', $request->telephone)
                      ->where('otp_code', $request->code)
                      ->where('otp_expires_at', '>', now())
                      ->first();

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide ou expiré'
            ], 401);
        }

        // Réinitialiser l'OTP après vérification réussie
        $client->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        // Générer un token d'authentification
        $token = $client->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'token' => $token,
            'client' => $client
        ]);
    }
}
