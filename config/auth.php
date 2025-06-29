<?php
return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'keycloak' => [
            'driver' => 'keycloak-web',
            'provider' => 'users',
        ],
    ],
];
