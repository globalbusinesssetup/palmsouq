<?php
return [
    'url' => [
        'APP_URL' => env('APP_URL'),
        'CLIENT_BASE_URL' => env('CLIENT_BASE_URL'),
    ],
    'redirect' => [
        'ORDER_DETAIL_REDIRECT' => '/user/order',
        'FRONTEND_SOCIAL_REDIRECT' => '/social-callback',
        'BACKEND_SOCIAL_REDIRECT' => '/api/v1/user/social-login/callback',
    ],
    'media'  => [
        'STORAGE' => env('MEDIA_STORAGE'),
        'CDN_URL' => env('CDN_URL'),
        'LOCAL' => 'LOCAL',
        'GCS' => 'GCS',
        'URL' => 'URL',
        'THUMB_PREFIX' => env('THUMB_PREFIX'),
        'DEFAULT_IMAGE' => env('DEFAULT_IMAGE'),
    ],
    'google_cloud' => [
        'PROJECT_ID' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'STORAGE_BUCKET' => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
        'STORAGE_PATH_PREFIX' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX'),
    ],
    'mail' => [
        'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS'),
        'MAIL_FROM_NAME' => env('MAIL_FROM_NAME'),
        'MAIL_PORT' => env('MAIL_PORT'),
        'MAIL_USERNAME' => env('MAIL_USERNAME'),
        'MAIL_PASSWORD' => env('MAIL_PASSWORD'),
        'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION'),
        'MAIL_HOST' => env('MAIL_HOST'),
    ],
    'oauth' => [
        'GOOGLE_CLIENT_ID' => env('GOOGLE_CLIENT_ID'),
        'GOOGLE_CLIENT_SECRET' => env('GOOGLE_CLIENT_SECRET'),
        'FACEBOOK_CLIENT_ID' => env('FACEBOOK_CLIENT_ID'),
        'FACEBOOK_CLIENT_SECRET' => env('FACEBOOK_CLIENT_SECRET'),
    ],
];

