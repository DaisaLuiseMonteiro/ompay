<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     title="Compte",
 *     description="Modèle de compte utilisateur",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique du compte"),
 *     @OA\Property(property="client_id", type="string", format="uuid", description="ID du client propriétaire"),
 *     @OA\Property(property="numero_compte", type="string", description="Numéro de compte unique"),
 *     @OA\Property(property="solde_initial", type="number", format="float", description="Solde initial du compte"),
 *     @OA\Property(property="devise", type="string", description="Devise du compte", example="XOF"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, description="Statut du compte"),
 *     @OA\Property(property="date_ouverture", type="string", format="date", description="Date d'ouverture du compte"),
 *     @OA\Property(property="code_secret", type="string", description="Code secret du compte (hashé)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière mise à jour")
 * )
 */
class Compte extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'client_id',
        'numero_compte',
        'solde_initial',
        'devise',
        'statut',
        'date_ouverture',
        'code_secret'
    ];

    protected $casts = [
        'id' => 'string',
        'solde_initial' => 'decimal:2',
        'date_ouverture' => 'datetime'
    ];

    /**
     * Relation avec le client propriétaire du compte
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relation avec les transactions du compte
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}