<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'email',
        'code',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    /**
     * Génère un nouveau code OTP
     */
    public static function generateCode($telephone, $email)
    {
        // Désactiver les anciens codes
        self::where('telephone', $telephone)
            ->orWhere('email', $email)
            ->update(['used' => true]);

        // Générer un code à 6 chiffres
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Créer un nouveau code OTP
        return self::create([
            'telephone' => $telephone,
            'email' => $email,
            'code' => $code,
            'expires_at' => now()->addMinutes(15), // Code valide 15 minutes
            'used' => false
        ]);
    }

    /**
     * Vérifie si le code est valide
     */
    public function isValid()
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Marque le code comme utilisé
     */
    public function markAsUsed()
    {
        $this->update(['used' => true]);
    }
}
