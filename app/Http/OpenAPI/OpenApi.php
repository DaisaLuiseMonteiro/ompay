<?php

declare(strict_types=1);

namespace App\Http\OpenAPI;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        version: '1.0.0',
        title: 'OMPay API',
        description: 'Documentation de l\'API OMPay',
        termsOfService: 'https://ompay.com/terms',
        contact: new OA\Contact(email: 'contact@ompay.com'),
        license: new OA\License(name: 'Propriétaire', url: 'https://ompay.com/license')
    ),
    servers: [
        new OA\Server(url: 'http://localhost:8000', description: 'API OMPay Server')
    ],
    security: [['bearerAuth' => []]]
)]
#[OA\Components(
    securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
            description: 'Entrez le token JWT: Bearer {token}'
        )
    ]
)]
class OpenApi
{
}