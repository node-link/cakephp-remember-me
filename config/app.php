<?php

return [

    'Security' => [
        'cookieKey' => env('SECURITY_COOKIE_KEY', env('SECURITY_SALT', '__SALT__')),
    ],

    'RememberMe' => [
        'field' => 'remember_me',
        'cookie' => [
            'name' => 'remember_me',
            'expires' => '+1 year',
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httpOnly' => true,
        ],
    ],

];
