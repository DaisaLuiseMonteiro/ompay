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

        // Log de débogage
        \Log::info('Vérification OTP', [
            'telephone' => $request->telephone,
            'code_fourni' => $request->code,
            'client_trouve' => $client ? 'oui' : 'non',
            'requete_sql' => Client::where('telephone', $request->telephone)
                                ->where('otp_code', $request->code)
                                ->where('otp_expires_at', '>', now())
                                ->toSql(),
            'bindings' => [
                'telephone' => $request->telephone,
                'code' => $request->code,
                'now' => now()
            ]
        ]);

        if (!$client) {
            // Vérifier si le client existe mais que le code est incorrect
            $clientWithPhone = Client::where('telephone', $request->telephone)->first();
            if ($clientWithPhone) {
                \Log::error('Échec vérification OTP', [
                    'code_fourni' => $request->code,
                    'code_attendu' => $clientWithPhone->otp_code,
                    'expiration' => $clientWithPhone->otp_expires_at,
                    'est_expire' => $clientWithPhone->otp_expires_at ? 
                                  ($clientWithPhone->otp_expires_at < now() ? 'oui' : 'non') : 'inconnu'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide ou expiré',
                'debug' => [
                    'code_fourni' => $request->code,
                    'code_attendu' => $clientWithPhone ? $clientWithPhone->otp_code : 'client_non_trouve',
                    'expiration' => $clientWithPhone ? $clientWithPhone->otp_expires_at : 'client_non_trouve',
                    'est_expire' => $clientWithPhone ? 
                                  ($clientWithPhone->otp_expires_at < now() ? 'oui' : 'non') : 'client_non_trouve'
                ]
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
