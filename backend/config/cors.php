<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', '_ignition/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // 1. Tu dominio de Vercel (PRODUCCIÓN)
        'https://aquaguardv-1.vercel.app',

        // 2. Variable de entorno (Por si configuras FRONTEND_URL en Render)
        env('FRONTEND_URL', 'http://localhost:4321'),

        // 3. Tus rutas locales (DESARROLLO - Para que siga funcionando en tu PC)
        'http://localhost:4321',
        'http://192.168.1.163:4321',
        'http://192.168.193.187:4321', // Agrego esta también por si acaso cambias de IP
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // ¡VITAL! Esto permite enviar cookies de sesión/tokens
    'supports_credentials' => true,

];