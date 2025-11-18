<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\Compte;

class Client extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Relation avec le compte du client
     */
    public function compte()
    {
        return $this->hasOne(Compte::class, 'client_id');
    }

    protected $fillable = [
        'id',
        'prenom',
        'nom',
        'email',
        'telephone',
        'otp_code',
        'otp_expires_at',
        'otp_type',
        'code_secret',
        'cni',
        'date_naissance',
        'adresse',
        'statut',
        'sexe',
        'email_verified_at',
        'remember_token'
    ];

    protected $hidden = [
        'otp_code',
        'otp_expires_at',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_expires_at' => 'datetime',
        'otp_verified_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
            $model->statut = $model->statut ?? 'actif';
        });
    }

    /**
     * Génère un nouveau code OTP pour le client
     */
    public function generateNewOtp($type = 'sms')
    {
        $this->otp_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->otp_expires_at = now()->addMinutes(30); // Augmenté à 30 minutes
        $this->otp_type = $type;
        $this->save();

        return $this->otp_code;
    }

    /**
     * Vérifie si le code OTP fourni est valide
     */
    public function isValidOtp($code)
    {
        return $this->otp_code === $code && 
               $this->otp_expires_at && 
               $this->otp_expires_at->isFuture();
    }

    /**
     * Invalide le code OTP actuel
     */
    public function invalidateOtp()
    {
        $this->otp_code = null;
        $this->otp_expires_at = null;
        $this->otp_type = null;
        $this->save();
    }

  public function routeNotificationForTwilio($notification)
{
    $originalNumber = $this->telephone;
    $phoneNumber = preg_replace('/[^0-9]/', '', $originalNumber);
    
    \Log::info('Formatage du numéro', [
        'original' => $originalNumber,
        'nettoyé' => $phoneNumber
    ]);
    
    if (str_starts_with($phoneNumber, '0')) {
        $phoneNumber = '+221' . substr($phoneNumber, 1);
    }
    elseif (str_starts_with($phoneNumber, '221')) {
        $phoneNumber = '+' . $phoneNumber;
    }
    elseif (!str_starts_with($phoneNumber, '+')) {
        $phoneNumber = '+221' . $phoneNumber;
    }
    
    \Log::info('Numéro formaté pour Twilio', [
        'final' => $phoneNumber
    ]);
    
    return $phoneNumber;
}

    public function routeNotificationForMail($notification)
    {
        return $this->email;
    }
}