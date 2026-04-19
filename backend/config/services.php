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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'endpoint' => env('ANTHROPIC_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
        'version' => env('ANTHROPIC_VERSION', '2023-06-01'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 500),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'endpoint' => 'https://api.groq.com/openai/v1/chat/completions',
        'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
        'ssl_verify' => filter_var(env('GROQ_SSL_VERIFY', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
