<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
    ],

    'ses' => [
        'key' => env('SES_KEY'),
        'secret' => env('SES_SECRET'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'twitter' => [
        'client_id' => '4RWjaGrwXn4NdrmlO4prqSRoo',         // Your GitHub Client ID
        'client_secret' => 'NMLJnUA6WtJ7zHu9pNlYtGPNdRdJMmJPXmp5gCiMHL6yv2vW0x', // Your GitHub Client Secret
        'redirect' => 'http://35.237.118.123/panel/user/twitter',
    ],
];
