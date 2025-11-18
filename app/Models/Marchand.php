<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marchand extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'prenom',
        'sexe',
        'telephone',
        'email',
        'adresse',
        'ville',
        'pays',
        'code_postal',
        'statut',
    ];

    protected $casts = [
        'statut' => 'boolean',
    ];

    /**
     * Obtenir les transactions associées au marchand
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
    
    /**
     * Obtenir le compte associé au marchand
     */
    public function compte()
    {
        return $this->hasOne(Compte::class, 'client_id');
    }
}