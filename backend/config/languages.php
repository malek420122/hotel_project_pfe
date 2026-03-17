<?php

return [
    'available' => explode(',', env('APP_AVAILABLE_LOCALES', 'fr,en,ar')),
    'default' => env('APP_LOCALE', 'fr'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
];
