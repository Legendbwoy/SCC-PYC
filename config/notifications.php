<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Engine Global Settings (Spec Section 10.6, 10.13, 10.14)
    |--------------------------------------------------------------------------
    */
    'enabled' => (bool) env('NOTIFICATION_ENABLED', true),
    'timezone' => env('NOTIFICATION_TIMEZONE', 'Africa/Accra'),
    'retry_limit' => (int) env('NOTIFICATION_RETRY_LIMIT', 5),

    // Retry delays in seconds: 1m, 5m, 15m, 1h, 3h (Spec Section 10.14)
    'retry_backoff_seconds' => [60, 300, 900, 3600, 10800],

    /*
    |--------------------------------------------------------------------------
    | Dedicated Notification Queues (Spec Section 10.13)
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'high_priority' => env('NOTIFICATION_HIGH_QUEUE', 'high_priority'),
        'default' => env('NOTIFICATION_QUEUE', 'notifications'),
        'bulk' => env('NOTIFICATION_BULK_QUEUE', 'bulk_notifications'),
        'webhooks' => env('NOTIFICATION_WEBHOOK_QUEUE', 'delivery_webhooks'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic Provider-Independent HTTP SMS Adapter (Spec Section 10.4 & 10.6)
    |--------------------------------------------------------------------------
    */
    'sms' => [
        'enabled' => (bool) env('SMS_ENABLED', true),
        'driver' => env('SMS_DRIVER', 'generic_http'),
        'generic_http' => [
            'endpoint' => env('SMS_ENDPOINT', ''),
            'method' => env('SMS_METHOD', 'POST'),
            'auth_type' => env('SMS_AUTH_TYPE', 'bearer'), // bearer | api_key_header | basic | custom_header
            'api_token' => env('SMS_API_TOKEN'),
            'auth_header_name' => env('SMS_AUTH_HEADER_NAME', 'X-API-Key'),
            'basic_username' => env('SMS_BASIC_USERNAME'),
            'basic_password' => env('SMS_BASIC_PASSWORD'),
            'sender_id' => env('SMS_SENDER_ID', 'SCC-PYC'),
            'timeout' => (int) env('SMS_TIMEOUT', 20),
            'encoding' => env('SMS_REQUEST_ENCODING', 'json'), // json | form
            'fields' => [
                'recipient' => env('SMS_RECIPIENT_FIELD', 'to'),
                'message' => env('SMS_MESSAGE_FIELD', 'message'),
                'sender' => env('SMS_SENDER_FIELD', 'sender'),
            ],
            'delivery_reports' => (bool) env('SMS_DELIVERY_REPORTS', false),
            'webhook_secret' => env('SMS_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Channel Settings (Spec Section 10.5)
    |--------------------------------------------------------------------------
    */
    'email' => [
        'enabled' => (bool) env('EMAIL_NOTIFICATIONS_ENABLED', true),
        'mailer' => env('MAIL_MAILER', 'smtp'),
        'reply_to' => env('MAIL_REPLY_TO_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | In-App Portal Channel Settings (Spec Section 10.1)
    |--------------------------------------------------------------------------
    */
    'in_app' => [
        'enabled' => true,
    ],
];
