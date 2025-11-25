<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    */
    'enabled' => env('ASSESSMENTS_ENABLED', true),
    'admin_only' => env('ASSESSMENTS_ADMIN_ONLY', true),
    'exposure_enabled' => env('ASSESSMENTS_EXPOSURE_ENABLED', false),
    'exposure_strict' => env('ASSESSMENTS_EXPOSURE_STRICT', false),
    'propagation_strict' => env('ASSESSMENTS_PROPAGATION_STRICT', true),
    'text_responses_enabled' => env('ASSESSMENTS_TEXT_RESPONSES_ENABLED', true),
    'preset_library' => env('ASSESSMENTS_PRESET_LIBRARY', true),
    'answers_library' => env('ASSESSMENTS_ANSWERS_LIBRARY', true),
    'review_required_for_pass' => env('ASSESSMENTS_REVIEW_REQUIRED_FOR_PASS', true),

    /*
    |--------------------------------------------------------------------------
    | Routing & Guards
    |--------------------------------------------------------------------------
    */
    'guards' => [
        'admin' => env('ASSESSMENTS_ADMIN_GUARD', 'web'),
        'candidate' => env('ASSESSMENTS_CANDIDATE_GUARD', 'web'),
    ],
    'middleware' => [
        'admin' => ['auth:doctor,web', config('jetstream.auth_session'), 'verified'],
        'admin_api' => ['auth:sanctum,web', 'verified'],
        'candidate' => ['auth:doctor,web', config('jetstream.auth_session'), 'verified'],
        'candidate_api' => ['auth:sanctum,web', 'verified'],
    ],
    'routes' => [
        'admin_prefix' => 'dashboard',
        'admin_name_prefix' => 'dashboard',
        'admin_api_prefix' => 'dashboard',
        'admin_api_name_prefix' => 'dashboard',
        'candidate_prefix' => '',
        'candidate_name_prefix' => 'assessments.candidate',
        'candidate_api_prefix' => 'api',
        'candidate_api_name_prefix' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Bindings
    |--------------------------------------------------------------------------
    |
    | Allow the host application to override key domain models. Defaults
    | keep backward compatibility with the Amryami platform.
    */
    'models' => [
        'category' => env('ASSESSMENTS_MODEL_CATEGORY'),
        'user' => env('ASSESSMENTS_MODEL_USER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Assembly & Attempts
    |--------------------------------------------------------------------------
    */
    'assembly' => [
        'strict' => env('ASSEMBLY_STRICT', true),
        'grace_seconds' => env('ASSESSMENTS_GRACE_SECONDS', 5),
    ],

    'cache' => [
        'pool_ttl' => env('ASSESSMENTS_POOL_CACHE_TTL', 300),
    ],

    'reminder' => [
        'log_channel' => env('ASSESSMENTS_REMINDER_LOG_CHANNEL', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Activation
    |--------------------------------------------------------------------------
    |
    | Allow one-time activation of an exam via a signed-like token. The prefix
    | controls the public URL, and redirect_route is used when no custom path
    | is stored on the exam record.
    */
    'activation' => [
        'prefix' => env('ASSESSMENTS_ACTIVATION_PREFIX', 'assessments/activate'),
        'middleware' => ['web'],
        'token_length' => env('ASSESSMENTS_ACTIVATION_TOKEN_LENGTH', 40),
        'expires_minutes' => env('ASSESSMENTS_ACTIVATION_EXPIRES_MINUTES', 1440), // 24h
        'redirect_route' => env('ASSESSMENTS_ACTIVATION_REDIRECT_ROUTE', 'assessments.candidate.exams.preview'),
    ],
];
