<?php

declare(strict_types=1);

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="API OMPay",
 *     version="1.0.0",
 *     description="Documentation de l'API OMPay",
 *     @OA\Contact(
 *         email="contact@ompay.com"
 *     ),
 *     @OA\License(
 *         name="Propriétaire",
 *         url="https://ompay.com/terms"
 *     )
 * )
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Authentification par JWT",
 *     name="JWT",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="bearerAuth"
 * )
 */

// Définition des schémas communs

/**
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="message", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="errors", type="object")
 * )
 */
