<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    /*
     * Entrada pela conta Google (OAuth 2.0).
     *
     * Sem as duas chaves o botao nao aparece em lugar nenhum -- ver
     * App\Support\ContaGoogle::configurado(). Elas saem do Google Cloud
     * Console, em Credenciais > ID do cliente OAuth, e o redirect_uri
     * cadastrado la tem de ser exatamente o desta configuracao.
     */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
