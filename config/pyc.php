<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Organization Configuration (Spec Section 18.1 & 18.3)
    |--------------------------------------------------------------------------
    */
    'organization' => [
        'name' => env('PYC_ORG_NAME', 'St. Cecilia Catholic Church — Parish Youth Council'),
        'short_name' => env('PYC_ORG_SHORT_NAME', 'SCC-PYC'),
        'parish_name' => env('PYC_PARISH_NAME', 'St. Cecilia Catholic Church'),
        'scope' => 'single_parish',
        'currency' => env('PYC_CURRENCY', 'GHS'),
        'timezone' => env('APP_TIMEZONE', 'Africa/Accra'),
        'financial_year_start_month' => 1,
        'financial_year_start_day' => 1,
        'reference_prefix' => env('PYC_REFERENCE_PREFIX', 'PYC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurable Branding Placeholders (Spec Section 18.3)
    |--------------------------------------------------------------------------
    */
    'branding' => [
        'tagline' => env('PYC_TAGLINE', 'Faith · Service · Leadership'),
        'logo_path' => env('PYC_LOGO_PATH'),
        'favicon_path' => env('PYC_FAVICON_PATH'),
        'primary_color' => env('PYC_PRIMARY_COLOR', '#0F2D59'),
        'accent_color' => env('PYC_ACCENT_COLOR', '#F5C542'),
        'contact_email' => env('PYC_CONTACT_EMAIL', 'pyc@stceciliagh.org'),
        'contact_phone' => env('PYC_CONTACT_PHONE', '+233 24 000 0000'),
        'address' => env('PYC_ADDRESS', 'St. Cecilia Catholic Church, Accra, Ghana'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Membership Rules & Google Sheets Sync (Spec Section 5 & 18.1)
    |--------------------------------------------------------------------------
    */
    'membership' => [
        'registration_source' => 'google_forms',
        'require_approval' => true,
        'account_activation' => 'invitation',
        'auto_create_portal_account' => false,
        'invitation_ttl_hours' => (int) env('PYC_INVITATION_TTL_HOURS', 72),
        'google_sheets' => [
            'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', ''),
            'sheet_range' => env('GOOGLE_SHEETS_RANGE', 'Form Responses 1!A1:Z2000'),
            'service_account_json_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH', ''),
            'api_key' => env('GOOGLE_SHEETS_API_KEY', ''),
            'default_field_mapping' => [
                'external_response_id' => ['Response ID', 'response_id'],
                'timestamp' => ['Timestamp', 'Submitted At', 'timestamp'],
                'first_name' => ['First Name', 'Given Name', 'first_name'],
                'middle_name' => ['Middle Name', 'Other Names', 'middle_name'],
                'last_name' => ['Last Name', 'Surname', 'Family Name', 'last_name'],
                'full_name' => ['Full Name', 'Name', 'full_name'],
                'phone' => ['Phone Number', 'Phone', 'Mobile Number', 'WhatsApp / Phone', 'phone'],
                'email' => ['Email Address', 'Email', 'email'],
                'date_of_birth' => ['Date of Birth', 'DOB', 'Birth Date', 'date_of_birth'],
                'gender' => ['Gender', 'Sex', 'gender'],
                'address' => ['Residential Address', 'Address', 'Location', 'address'],
                'occupation' => ['Occupation', 'Profession / School', 'occupation'],
                'group_name' => ['Youth Group / Society', 'Group', 'Ministry', 'group'],
                'emergency_contact_name' => ['Emergency Contact Name', 'Guardian Name', 'emergency_contact_name'],
                'emergency_contact_phone' => ['Emergency Contact Phone', 'Guardian Phone', 'emergency_contact_phone'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Finance & Accounting Controls (Spec Section 6, 8 & 18.1)
    |--------------------------------------------------------------------------
    */
    'finance' => [
        'monthly_dues_pesewas' => (int) env('PYC_MONTHLY_DUES_PESEWAS', 1000),
        'dues_frequency' => 'monthly',
        'accounting_basis' => env('PYC_ACCOUNTING_BASIS', 'cash'), // cash (operational receivables) | accrual
        'cash_payment_requires_approval' => true,
        'dual_authorization' => true,
        'automatic_receipts' => true,
        'allow_partial_payments' => true,
        'allow_advance_payments' => true,
        'financial_year' => 'calendar',
        'financial_deletion_allowed' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Maker-Checker Approval Matrix (Spec Section 4.3 & 19.1)
    |--------------------------------------------------------------------------
    */
    'approvals' => [
        'enforce_maker_checker' => true,
        'allow_super_admin_self_approval' => false,
        'cash_payment' => [
            'recorders' => ['Treasurer', 'Financial Secretary'],
            'approvers' => ['PYC President', 'Treasurer', 'Financial Secretary'],
        ],
        'expense' => [
            'submitters' => ['Treasurer', 'Financial Secretary', 'General Secretary'],
            'approvers' => ['PYC President', 'Treasurer'],
            'dual_auth_threshold_pesewas' => 0, // Every expense requires independent approval by default
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments Configuration (Spec Section 7 & 18.1)
    |--------------------------------------------------------------------------
    */
    'payments' => [
        'paystack_enabled' => (bool) env('PAYSTACK_ENABLED', true),
        'cash_enabled' => true,
        'webhook_verification' => true,
        'idempotency_required' => true,
        'paystack' => [
            'public_key' => env('PAYSTACK_PUBLIC_KEY'),
            'secret_key' => env('PAYSTACK_SECRET_KEY'),
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
            'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security & Two-Factor Authentication Policy (Spec Section 4.4)
    |--------------------------------------------------------------------------
    */
    'security' => [
        'enforce_admin_2fa' => (bool) env('PYC_ENFORCE_ADMIN_2FA', true),
        'two_factor_required_roles' => [
            'Super Administrator',
            'PYC President',
            'Treasurer',
            'Financial Secretary',
            'General Secretary',
            'Auditor',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Drivers Summary (Spec Section 10 & 18.1)
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'sms_driver' => env('SMS_DRIVER', 'generic_http'),
        'email_driver' => env('MAIL_MAILER', 'smtp'),
        'in_app_enabled' => true,
    ],
];
