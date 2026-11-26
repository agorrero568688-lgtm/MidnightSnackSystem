<?php

use App\Support\S3NoAcl;

// Supabase rejects ACL headers, and the adapter otherwise sends one on every
// write. Keep small uploads on PutObject so `before_upload` can strip it.
$s3Options = [
    'before_upload' => [S3NoAcl::class, 'stripAcl'],
    'mup_threshold' => 100 * 1024 * 1024,
];

// AWS_MEDIA_URL is the public bucket's browser URL. Deriving the bucket from
// it keeps uploads and URLs pointed at the same place even when only one of
// the two media variables is set in the deployment environment.
$mediaUrl = env('AWS_MEDIA_URL');
$legacyMediaUrl = env('AWS_URL');
$mediaBucket = env('AWS_MEDIA_BUCKET');
$bucketUrl = $mediaUrl ?: $legacyMediaUrl;

if (is_string($bucketUrl) && preg_match('#/object/public/([^/?#]+)#', $bucketUrl, $matches) === 1) {
    $mediaBucket = $matches[1];
}

$mediaBucket ??= env('AWS_BUCKET', 'media');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        /*
         * On Vercel the container filesystem is ephemeral, so the "local"
         * (private receipts) and "public" (product photos and GCash QR) disks
         * switch to Supabase Storage's S3 API when the drivers below are set
         * to "s3". The legacy "s3" disk remains for older stored URLs.
         */
        'local' => env('PROOF_DISK_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => env('AWS_PROOFS_BUCKET', 'proofs'),
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
                'directory_visibility' => 'private',
                'throw' => true,
                'report' => false,
                'options' => $s3Options,
                'http' => [
                    'connect_timeout' => 3,
                    'timeout' => 5,
                ],
                'retries' => 1,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'serve' => true,
                'throw' => false,
                'report' => false,
            ],

        'public' => env('PUBLIC_DISK_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
                'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket' => $mediaBucket,
                'endpoint' => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
                'url' => $mediaUrl,
                'directory_visibility' => 'private',
                'throw' => true,
                'report' => false,
                'options' => $s3Options,
                'http' => [
                    'connect_timeout' => 3,
                    'timeout' => 5,
                ],
                'retries' => 1,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => '/storage',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => $mediaBucket,
            'url' => $mediaUrl ?: $legacyMediaUrl,
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
            'throw' => true,
            'report' => false,
            'options' => $s3Options,
            'http' => [
                'connect_timeout' => 3,
                'timeout' => 5,
            ],
            'retries' => 1,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
