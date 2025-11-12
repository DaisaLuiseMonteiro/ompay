<?php

return [
    'info' => [
        'title' => 'API OMPay',
        'version' => '1.0.0',
        'description' => 'Documentation de l\'API OMPay',
        'contact' => [
            'email' => 'contact@ompay.com'
        ],
        'license' => [
            'name' => 'Propriétaire',
            'url' => 'https://ompay.com/terms'
        ]
    ],
    'servers' => [
        [
            'url' => env('APP_URL', 'http://localhost:8000') . '/api',
            'description' => 'Serveur API principal'
        ]
    ],
    'paths' => [
        '/*' => [
            'get' => [
                'operation_sort' => 'method',
            ],
            'post' => [
                'operation_sort' => 'method',
            ],
            'put' => [
                'operation_sort' => 'method',
            ],
            'patch' => [
                'operation_sort' => 'method',
            ],
            'delete' => [
                'operation_sort' => 'method',
            ],
        ]
    ]
];
