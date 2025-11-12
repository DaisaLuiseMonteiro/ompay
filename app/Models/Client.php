<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     title="Client",
 *     description="Modèle de client",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique du client"),
 *     @OA\Property(property="user_id", type="string", format="uuid", description="ID de l'utilisateur associé"),
 *     @OA\Property(property="prenom", type="string", description="Prénom du client"),
 *     @OA\Property(property="nom", type="string", description="Nom du client"),
 *     @OA\Property(property="adresse", type="string", description="Adresse du client"),
 *     @OA\Property(property="ville", type="string", description="Ville du client"),
 *     @OA\Property(property="pays", type="string", description="Pays du client"),
 *     @OA\Property(property="telephone", type="string", description="Numéro de téléphone du client"),
 *     @OA\Property(property="piece_identite", type="string", description="Numéro de pièce d'identité"),
 *     @OA\Property(property="type_piece", type="string", description="Type de pièce d'identité (CNI, Passeport, etc.)"),
 *     @OA\Property(property="date_emission", type="string", format="date", description="Date d'émission de la pièce d'identité"),
 *     @OA\Property(property="date_expiration", type="string", format="date", description="Date d'expiration de la pièce d'identité"),
 *     @OA\Property(property="statut", type="string", description="Statut du client (actif, inactif, suspendu)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière mise à jour")
 * )
 */
class Client extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
       'id',
    'prenom',
    'nom',
    'date_naissance',
    'adresse',
    'telephone',
    'cni',
    'code_secret',
    'statut',
    'sexe',
    'email'
    ];

    protected $casts = [
        'id' => 'string',
        'date_emission' => 'date',
        'date_expiration' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function compte(): HasOne
    {
        return $this->hasOne(Compte::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'client_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}