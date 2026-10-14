<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Layanan hitung Python
    |--------------------------------------------------------------------------
    |
    | Dipanggil lewat PerhitunganClientInterface. Header X-Internal-Token
    | diambil dari token di sini. Timeout dan retry sengaja pendek karena
    | pemanggilan berjalan di dalam request web; jeda panjang untuk recovering
    | ulang ada di NilaiUlangJob.
    |
    */

    'perhitungan' => [
        'url' => env('PERHITUNGAN_URL', 'http://localhost:8001'),
        'token' => env('PERHITUNGAN_TOKEN'),
        'timeout' => (int) env('PERHITUNGAN_TIMEOUT', 5),
        'retry' => (int) env('PERHITUNGAN_RETRY', 2),
    ],

];
