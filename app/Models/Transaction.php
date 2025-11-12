<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     description="Modèle de transaction",
 *     @OA\Property(property="id", type="string", format="uuid", description="ID unique de la transaction"),
 *     @OA\Property(property="compte_id", type="string", format="uuid", description="ID du compte émetteur"),
 *     @OA\Property(property="client_id", type="string", format="uuid", description="ID du client effectuant la transaction"),
 *     @OA\Property(property="marchand_id", type="string", format="uuid", nullable=true, description="ID du marchand (si applicable)"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "paiement"}, description="Type de transaction"),
 *     @OA\Property(property="montant", type="number", format="float", description="Montant de la transaction"),
 *     @OA\Property(property="frais", type="number", format="float", description="Frais de transaction"),
 *     @OA\Property(property="solde_avant", type="number", format="float", description="Solde du compte avant la transaction"),
 *     @OA\Property(property="solde_apres", type="number", format="float", description="Solde du compte après la transaction"),
 *     @OA\Property(property="devise", type="string", description="Devise de la transaction"),
 *     @OA\Property(property="statut", type="string", enum={"en_attente", "terminee", "annulee", "echouee"}, description="Statut de la transaction"),
 *     @OA\Property(property="reference", type="string", description="Référence unique de la transaction"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Description de la transaction"),
 *     @OA\Property(property="date_transaction", type="string", format="date-time", description="Date et heure de la transaction"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Date de création"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière mise à jour")
 * )
 */
class Transaction extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'compte_id',
        'client_id',
        'marchand_id',
        'type',
        'montant',
        'frais',
        'solde_avant',
        'solde_apres',
        'devise',
        'statut',
        'reference',
        'description',
        'date_transaction'
    ];

    protected $casts = [
        'id' => 'string',
        'montant' => 'decimal:2',
        'frais' => 'decimal:2',
        'solde_avant' => 'decimal:2',
        'solde_apres' => 'decimal:2',
        'date_transaction' => 'datetime'
    ];

    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Illuminate\Support\Str::uuid();
            }
            if (empty($model->reference)) {
                $model->reference = 'TRX' . strtoupper(uniqid());
            }
            if (empty($model->date_transaction)) {
                $model->date_transaction = now();
            }
        });
    }
}