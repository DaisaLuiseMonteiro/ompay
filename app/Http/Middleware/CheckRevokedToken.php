<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\Token;

class CheckRevokedToken
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        
        if ($user) {
            $token = $user->token();
            
            // Vérifier si le token est révoqué
            if ($token && $token->is_revoked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token révoqué. Veuillez vous reconnecter.'
                ], 401);
            }
        }

        return $next($request);
    }
}
