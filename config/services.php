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

    // 'openwa' => [
    //     'base_url' => env('OPENWA_BASEURL'),
    //     'api_key' => env('OPENWA_APIKEY'),
    //     'session_id' => env('OPENWA_SESSIONID'),
    //     'endpoint' => env('OPENWA_ENDPOINT'),
    //     'phone_field' => env('OPENWA_PHONE_FIELD', 'phone'),
    //     'message_field' => env('OPENWA_MESSAGE_FIELD', 'message'),
    //     'session_field' => env('OPENWA_SESSION_FIELD', 'session'),
    //     'api_key_header' => env('OPENWA_APIKEY_HEADER', 'Authorization'),
    //     'api_key_prefix' => env('OPENWA_APIKEY_PREFIX', 'Bearer '),
    // ],

    'openwa' => [
        'base_url' => env('OPENWA_BASEURL'),
        'api_key' => env('OPENWA_APIKEY'),
        'session_id' => env('OPENWA_SESSIONID'),
        'endpoint' => env('OPENWA_ENDPOINT'),
        'phone_field' => env('OPENWA_PHONE_FIELD', 'phone'),
        'message_field' => env('OPENWA_MESSAGE_FIELD', 'message'),
        'session_field' => env('OPENWA_SESSION_FIELD', 'session'),
        'api_key_header' => env('OPENWA_APIKEY_HEADER', 'Authorization'),
        'api_key_prefix' => env('OPENWA_APIKEY_PREFIX', 'Bearer '),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
