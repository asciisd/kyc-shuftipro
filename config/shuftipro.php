<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ShuftiPro API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ShuftiPro API integration including credentials,
    | endpoints, and timeout settings.
    |
    */

    'api' => [
        'client_id' => env('SHUFTIPRO_CLIENT_ID'),
        'secret_key' => env('SHUFTIPRO_SECRET_KEY'),
        'base_url' => env('SHUFTIPRO_BASE_URL', 'https://api.shuftipro.com'),
        'timeout' => env('SHUFTIPRO_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | ShuftiPro Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook handling including URLs and security settings.
    |
    */

    'webhook' => [
        'secret_key' => env('SHUFTIPRO_WEBHOOK_SECRET'),
        'callback_url' => env('SHUFTIPRO_CALLBACK_URL'),
        'redirect_url' => env('SHUFTIPRO_REDIRECT_URL'),
        'signature_validation' => env('SHUFTIPRO_WEBHOOK_SIGNATURE_VALIDATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | ShuftiPro IDV Journeys
    |--------------------------------------------------------------------------
    |
    | Configuration for IDV (Identity Verification) journeys including
    | default journey ID and custom journey configurations.
    |
    */

    'idv_journeys' => [
        'default_journey_id' => env('SHUFTIPRO_DEFAULT_JOURNEY_ID'),
        'enabled' => env('SHUFTIPRO_JOURNEYS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | ShuftiPro Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for logging ShuftiPro API requests and responses.
    |
    */

    'logging' => [
        'enabled' => env('SHUFTIPRO_LOGGING_ENABLED', true),
        'channel' => env('SHUFTIPRO_LOG_CHANNEL', 'daily'),
        'level' => env('SHUFTIPRO_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | ShuftiPro Document Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for document handling including storage and processing.
    |
    */

    'documents' => [
        'auto_download' => env('SHUFTIPRO_AUTO_DOWNLOAD_DOCUMENTS', true),
        'storage_disk' => env('SHUFTIPRO_DOCUMENT_STORAGE_DISK', 's3'),
        'storage_path' => env('SHUFTIPRO_DOCUMENT_STORAGE_PATH', 'shuftipro/documents'),
        'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'mp4'],
        'max_file_size' => env('SHUFTIPRO_MAX_FILE_SIZE', 10485760), // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | ShuftiPro Verification Settings
    |--------------------------------------------------------------------------
    |
    | General verification settings and options.
    |
    */

    'verification' => [
        'default_country' => env('SHUFTIPRO_DEFAULT_COUNTRY', ''),
        'default_language' => env('SHUFTIPRO_DEFAULT_LANGUAGE', 'en'),
        'allowed_countries' => env('SHUFTIPRO_ALLOWED_COUNTRIES', ''),
        'denied_countries' => env('SHUFTIPRO_DENIED_COUNTRIES', ''),
        'enable_duplicate_detection' => env('SHUFTIPRO_ENABLE_DUPLICATE_DETECTION', true),
        'verification_timeout' => env('SHUFTIPRO_VERIFICATION_TIMEOUT', 3600), // 1 hour
    ],
];
