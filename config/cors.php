<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The QCourts marketing website (static HTML/Tailwind, deployed to Netlify)
    | calls this API from the browser, so its origin must be allowed here.
    | Set FRONTEND_URL to the production website URL in production. While the
    | domain is still TBD, the Netlify preview pattern below keeps deploys
    | working; tighten this before launch.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL'),
        'http://localhost:5173',
        'http://localhost:3000',
    ])),

    'allowed_origins_patterns' => ['/^https:\/\/.*\.netlify\.app$/'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
