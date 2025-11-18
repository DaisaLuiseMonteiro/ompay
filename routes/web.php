<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Documentation API
// Route::get('/api/documentation', '\L5Swagger\Http\Controllers\SwaggerController@api')->name('l5swagger.api');

// Routes de test
Route::get('/test-email', function() {
    try {
        $client = \App\Models\Client::first();
        if (!$client) {
            // Créer un client de test si aucun n'existe
            $client = \App\Models\Client::create([
                'prenom' => 'Test',
                'nom' => 'User',
                'email' => 'votre_email@example.com', // Remplacez par une vraie adresse email
                'telephone' => '+221770000000', // Remplacez par un vrai numéro
                'codepin' => '000000'
            ]);
        }
        
        $client->notify(new \App\Notifications\OtpNotification('123456', 'email'));
        return "Email de test envoyé à {$client->email} !";
    } catch (\Exception $e) {
        return "Erreur lors de l'envoi de l'email: " . $e->getMessage();
    }
});

Route::get('/test-sms', function() {
    try {
        $client = \App\Models\Client::first();
        if (!$client) {
            // Créer un client de test si aucun n'existe
            $client = \App\Models\Client::create([
                'prenom' => 'Test',
                'nom' => 'User',
                'email' => 'loudaisa02@gmail.com', // Remplacez par une vraie adresse email
                'telephone' => '+221775312571', // Remplacez par un vrai numéro
                'codepin' => '000000'
            ]);
        }
        
        if (config('services.twilio.enabled')) {
            $client->notify(new \App\Notifications\OtpNotification('123456', 'sms'));
            return "SMS de test envoyé à {$client->telephone} !";
        } else {
            return "L'envoi de SMS est désactivé (vérifiez la configuration Twilio)";
        }
    } catch (\Exception $e) {
        return "Erreur lors de l'envoi du SMS: " . $e->getMessage();
    }
});