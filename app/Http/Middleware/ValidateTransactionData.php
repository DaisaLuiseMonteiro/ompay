<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\TransactionRequest;

class ValidateTransactionData
{
    public function handle(Request $request, Closure $next)
    {
        $validator = Validator::make($request->all(), (new TransactionRequest())->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        return $next($request);
    }
}