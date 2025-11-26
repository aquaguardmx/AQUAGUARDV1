<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '_ignition/*'],

    'allowed_methods' => ['*'],

    // OPCIÓN A: Permitir todo (Rápido para desarrollo)
    // 'allowed_origins' => ['*'], 
    
    // OPCIÓN B (RECOMENDADA): Permitir tu IP local
    'allowed_origins' => [
        'http://localhost:4321',
        'http://192.168.1.163:4321', // <--- TU IP Y PUERTO DE ASTRO
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // CAMBIO IMPORTANTE: Pon esto en true
    'supports_credentials' => true, 
];