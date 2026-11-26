<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'cron' => [
        'secret' => env('CRON_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vercel Blob
    |--------------------------------------------------------------------------
    |
    | Public menu art and the GCash QR use one Blob store; private receipts use
    | another. Values default to local Laravel disks so development and tests
    | never need network access.
    |
    */
    'blob' => [
        'api_url' => env('BLOB_API_URL', 'https://vercel.com/api/blob'),

        'media' => [
            'driver' => env('MEDIA_DRIVER', 'local'),
            'token' => env('BLOB_PUBLIC_TOKEN'),
            'store_id' => env('BLOB_PUBLIC_STORE_ID'),
            'base_url' => env('BLOB_PUBLIC_BASE_URL'),
            'cache_control' => env('BLOB_MEDIA_CACHE_SECONDS', 86400),
        ],

        'proofs' => [
            'driver' => env('PROOF_DRIVER', 'local'),
            'token' => env('BLOB_PRIVATE_TOKEN'),
            'store_id' => env('BLOB_PRIVATE_STORE_ID'),
            'base_url' => env('BLOB_PRIVATE_BASE_URL'),
            'cache_control' => env('BLOB_PROOF_CACHE_SECONDS', 60),
        ],

        // Old Supabase public URLs stay readable until media is re-uploaded.
        'legacy_media_url' => env('LEGACY_MEDIA_URL', env('AWS_MEDIA_URL')),

        // Read-only fallback for receipts still stored in Supabase.
        'legacy_proofs' => [
            'url' => env('LEGACY_SUPABASE_URL'),
            'bucket' => env('LEGACY_PROOFS_BUCKET', 'proofs'),
            'key' => env('LEGACY_SUPABASE_SERVICE_ROLE_KEY'),
        ],
    ],

];
