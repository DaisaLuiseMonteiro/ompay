<?php

namespace App\Http\OpenAPI\Requests;

/**
 * @OA\Schema(
 *     schema="ClientInput",
 *     type="object",
 *     required={"nom", "prenom", "date_naissance", "cni", "telephone", "solde_initial"},
 *     @OA\Property(property="nom", type="string", example="Doe"),
 *     @OA\Property(property="prenom", type="string", example="John"),
 *     @OA\Property(property="date_naissance", type="string", format="date", example="1990-01-01"),
 *     @OA\Property(property="adresse", type="string", nullable=true, example="123 Rue Exemple"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="cni", type="string", example="1234567890123"),
 *     @OA\Property(property="solde_initial", type="number", format="float", example=10000)
 * )
 */
class CompteRequest
{
    // 
}
