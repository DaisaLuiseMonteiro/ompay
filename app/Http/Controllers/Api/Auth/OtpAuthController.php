<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpCodeMail;
use Illuminate\Support\Facades\Validator;

class OtpAuthController extends Controller
{
    /**
     * Envoie un code OTP par email et SMS
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'telephone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Générer un nouveau code OTP
            $otpCode = OtpCode::generateCode($request->telephone, $request->email);

            // Envoyer le code par email
            Mail::to($request->email)->send(new OtpCodeMail($otpCode->code));

            // Ici, vous devriez ajouter la logique pour envoyer le code par SMS
            // Par exemple : $this->sendSms($request->telephone, "Votre code de vérification est: " . $otpCode->code);

            return response()->json([
                'success' => true,
                'message' => 'Code OTP envoyé avec succès',
                'expires_in' => $otpCode->expires_at->diffForHumans()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi du code OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
            'email' => 'required|email|max:255',
            'code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Rechercher le code OTP non utilisé et non expiré
        $otpCode = OtpCode::where('telephone', $request->telephone)
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otpCode) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide ou expiré'
            ], 400);
        }

        // Marquer le code comme utilisé
        $otpCode->markAsUsed();

        // Ici, vous pouvez ajouter la logique pour connecter l'utilisateur
        // Par exemple : auth()->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Code OTP vérifié avec succès',
            'token' => 'votre_jwt_token_ou_autre_mécanisme_d_authentification'
        ]);
    }

    /**
     * Envoie un SMS (à implémenter selon votre fournisseur de SMS)
     */
    protected function sendSms($phoneNumber, $message)
    {
        // Implémentez ici l'envoi de SMS avec votre fournisseur de services SMS
        // Par exemple avec Twilio, Nexmo, etc.
        // Ceci est un exemple générique
        
        // Exemple avec un fournisseur hypothétique :
        // $client = new \YourSmsProvider\Client(config('services.sms.key'));
        // $client->send($phoneNumber, $message);
        
        // Pour le moment, on se contente de logger le message
        \Log::info("SMS envoyé à $phoneNumber: $message");
        
        return true;
    }
}
