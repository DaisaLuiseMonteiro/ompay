<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
        'remember_token',
        'refresh_token',
        'refresh_token_expires_at'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $dates = [
        'refresh_token_expires_at'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function isActive()
    {
        return $this->statut === 'actif';
    }
}