<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi CORS
|--------------------------------------------------------------------------
| Dibaca oleh middleware global HandleCors (bawaan Laravel) untuk semua
| path api/*. Frontend Vite berjalan di http://localhost:5173, jadi origin
| itu diizinkan secara default; tambah origin lain lewat env FRONTEND_URL
| (pisahkan koma bila lebih dari satu). supports_credentials true karena
| frontend mengirim request dengan credentials mode 'include' (withCredentials),
| sehingga browser mewajibkan header Access-Control-Allow-Credentials: true.
| Origin harus eksplisit (tidak boleh '*') bila credentials aktif.
*/

$frontendUrls = array_values(array_unique(array_filter(array_map(
    'trim',
    explode(',', (string) env('FRONTEND_URL', 'http://localhost:5173,http://127.0.0.1:5173'))
))));

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $frontendUrls,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
