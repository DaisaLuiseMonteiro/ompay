<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\CompteController;

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
    });

    // Documentation Swagger
    Route::get('/documentation', '\L5Swagger\Http\Controllers\SwaggerController@api')->name('api.documentation');

    // Transactions
    Route::prefix('transactions')->middleware('auth:api')->group(function () {
        Route::post('/transfert', [TransactionController::class, 'effectuerTransfert']);
        Route::post('/paiement', [TransactionController::class, 'effectuerPaiement']);
        Route::get('/historique', [TransactionController::class, 'historique']);
        Route::get('/{id}', [TransactionController::class, 'show']);
    });

    // Comptes
    Route::prefix('comptes')->middleware('auth:api')->group(function () {
        Route::post('/creer', [CompteController::class, 'creerCompte']);
        Route::get('/solde', [CompteController::class, 'consulterSolde']);
    });
});
