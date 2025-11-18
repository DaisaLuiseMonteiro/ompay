<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\CompteController;
use App\Http\Controllers\Api\V1\ClientController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
// Version 1 de l'API
Route::prefix('v1')->group(function () {
    // Authentification
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken'])->middleware('auth:api');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    });

    // Routes publiques
    Route::post('/comptes', [CompteController::class, 'creerCompte']);

    // Documentation Swagger
    Route::get('/documentation', '\L5Swagger\Http\Controllers\SwaggerController@api')->name('api.documentation');

    // Routes protégées par authentification Passport
    Route::middleware(['auth:api', 'check.token'])->group(function () {
        // Transactions
        Route::prefix('transactions')->group(function () {
            Route::post('/transfert', [TransactionController::class, 'effectuerTransfert']);
            Route::post('/paiement', [TransactionController::class, 'effectuerPaiement']);
            Route::get('/historique', [TransactionController::class, 'historique']);
            Route::get('/{id}', [TransactionController::class, 'show']);
        });

        // Comptes
        Route::prefix('comptes')->group(function () {
            Route::get('/solde', [CompteController::class, 'consulterSolde']);
            Route::get('/details', [CompteController::class, 'afficherDetailsCompte']);
        });
    });
});