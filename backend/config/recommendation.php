<?php

return [
    'minimum_interactions' => env('RECOMMENDATION_MINIMUM_INTERACTIONS', 3),
    'time_decay_half_life_days' => env('RECOMMENDATION_TIME_DECAY_HALF_LIFE_DAYS', 90),

    'action_weights' => [
        'view' => env('RECOMMENDATION_WEIGHT_VIEW', 1.0),
        'click' => env('RECOMMENDATION_WEIGHT_CLICK', 2.0),
        'reservation' => env('RECOMMENDATION_WEIGHT_RESERVATION', 5.0),
    ],

    'collaborative_weight' => env('RECOMMENDATION_COLLABORATIVE_WEIGHT', 0.25),
    'collaborative_cache_ttl' => env('RECOMMENDATION_COLLABORATIVE_CACHE_TTL', 900),
    'collaborative_similar_users_limit' => env('RECOMMENDATION_COLLABORATIVE_SIMILAR_USERS_LIMIT', 100),
    'collaborative_hotels_limit' => env('RECOMMENDATION_COLLABORATIVE_HOTELS_LIMIT', 80),

    'theme_bonus_cap' => env('RECOMMENDATION_THEME_BONUS_CAP', 30.0),
    'review_bonus_cap' => env('RECOMMENDATION_REVIEW_BONUS_CAP', 24.0),
];
