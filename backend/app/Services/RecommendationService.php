<?php
namespace App\Services;

use App\Models\Hotel;
use App\Models\Chambre;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;

class RecommendationService
{
    protected Client $mongo;
    protected string $dbName;

    public function __construct()
    {
        $dsn = env('MONGO_DSN', env('MONGO_URL', 'mongodb://127.0.0.1:27017'));
        $this->mongo = new Client($dsn);
        $this->dbName = env('MONGO_DB', 'hotel_project');
    }

    /**
     * Record a user activity into MongoDB `user_activities` collection.
     */
    public function recordActivity(string $userId, ?string $hotelId, string $actionType, array $categoryTags = []) : void
    {
        $col = $this->mongo->selectDatabase($this->dbName)->user_activities;
        $payload = [
            'user_id' => (string) $userId,
            'hotel_id' => $hotelId ? (string) $hotelId : null,
            'action_type' => $actionType,
            'category_tags' => array_values($categoryTags),
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
        ];

        $col->insertOne($payload);

        Log::info('Recommendation activity recorded', [
            'user_id' => (string) $userId,
            'hotel_id' => $payload['hotel_id'],
            'action_type' => $actionType,
            'category_tags' => $payload['category_tags'],
        ]);
    }

    /**
     * Build a personalized feed of hotels for a user.
     *
     * The feed mixes activity-based relevance, review-theme matching and a small
     * wildcard quota to avoid tunnel vision.
     */
    public function getPersonalizedFeed(string $userId, int $limit = 10) : array
    {
        $limit = max(1, $limit);
        $wildcardQuota = min(2, max(1, (int) ceil($limit / 5)));
        $mainQuota = max(0, $limit - $wildcardQuota);

        $db = $this->mongo->selectDatabase($this->dbName);
        $config = $this->getRecommendationConfig();

        // 1. STRATÉGIE DE FALLBACK (Cold Start)
        $interactionCount = $db->user_activities->countDocuments(['user_id' => $userId]);
        if ($interactionCount < $config['minimum_interactions']) {
            $mainItems = [];
            $mainItems = array_values(array_merge(
                $mainItems,
                $this->fallbackPopularHotels($mainItems, $limit)
            ));
            return $mainItems;
        }

        $activitySummary = $this->summarizeUserActivities($db, $userId);
        $topTags = $activitySummary['tags'];
        $themeSearchTerms = $this->buildThemeSearchTerms($topTags);
        $reviewMatches = $this->matchThemesFromReviews($db, $themeSearchTerms);
        $collaborativeScores = $this->getCollaborativeScores($db, $userId, array_keys($activitySummary['hotels']));

        $rankedCandidates = $this->mergeSignals(
            $activitySummary['hotels'],
            $reviewMatches,
            $topTags,
            $collaborativeScores
        );

        $mainItems = $this->hydrateHotelsAndFormat($rankedCandidates, $mainQuota);
        if (count($mainItems) < $mainQuota) {
            $mainItems = array_values(array_merge(
                $mainItems,
                $this->fallbackPopularHotels($mainItems, $mainQuota - count($mainItems))
            ));
        }

        $wildcards = $this->selectWildcards($mainItems, $wildcardQuota, $limit);

        Log::info('Recommendation computed', [
            'user_id' => $userId,
            'limit' => $limit,
            'activity_count' => $interactionCount,
            'collaborative_items' => count($collaborativeScores),
            'config' => $config,
        ]);

        return array_values(array_merge($mainItems, $wildcards));
    }

    /**
     * Backward-compatible wrapper used by the existing controller.
     */
    public function getSuggestedHotels(string $userId, int $limit = 20) : array
    {
        return $this->getPersonalizedFeed($userId, $limit);
    }

    private function summarizeUserActivities(object $db, string $userId) : array
    {
        $config = $this->getRecommendationConfig();
        $weights = $config['action_weights'];
        $decayHalfLifeDays = max(1, (int) ($config['time_decay_half_life_days'] ?? 90));

        $pipeline = [
            ['$match' => ['user_id' => $userId]],
            ['$addFields' => [
                'normalized_action' => ['$toLower' => ['$ifNull' => ['$action_type', 'view']]],
                'base_weight' => [
                    '$switch' => [
                        'branches' => [
                            [
                                'case' => ['$in' => [['$toLower' => ['$ifNull' => ['$action_type', 'view']]], ['reservation', 'book', 'booking', 'reserved', 'booked']]],
                                'then' => $weights['reservation'],
                            ],
                            [
                                'case' => ['$in' => [['$toLower' => ['$ifNull' => ['$action_type', 'view']]], ['click', 'clicked', 'tap']]],
                                'then' => $weights['click'],
                            ],
                            [
                                'case' => ['$in' => [['$toLower' => ['$ifNull' => ['$action_type', 'view']]], ['view', 'seen', 'impression', 'page_view']]],
                                'then' => $weights['view'],
                            ],
                        ],
                        'default' => $weights['view'],
                    ],
                ],
                'time_decay' => [
                    '$let' => [
                        'vars' => [
                            'ageMillis' => [
                                '$subtract' => [new \MongoDB\BSON\UTCDateTime(), '$created_at'],
                            ],
                        ],
                        'in' => [
                            '$pow' => [
                                0.5,
                                [
                                    '$divide' => [
                                        ['$divide' => ['$$ageMillis', 1000 * 60 * 60 * 24]],
                                        $decayHalfLifeDays,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],
            ['$addFields' => [
                'activity_weight' => ['$multiply' => ['$base_weight', '$time_decay']],
            ]],
            ['$facet' => [
                'hotels' => [
                    ['$match' => ['hotel_id' => ['$ne' => null]]],
                    ['$group' => [
                        '_id' => '$hotel_id',
                        'score' => ['$sum' => '$activity_weight'],
                        'reservations' => ['$sum' => ['$cond' => [['$in' => ['$normalized_action', ['reservation', 'book', 'booking', 'reserved', 'booked']]], 1, 0]]],
                        'clicks' => ['$sum' => ['$cond' => [['$in' => ['$normalized_action', ['click', 'clicked', 'tap']]], 1, 0]]],
                        'views' => ['$sum' => ['$cond' => [['$in' => ['$normalized_action', ['view', 'seen', 'impression', 'page_view']]], 1, 0]]],
                        'last_activity_at' => ['$max' => '$created_at'],
                        'tags' => ['$push' => '$category_tags'],
                    ]],
                    ['$sort' => ['score' => -1, 'last_activity_at' => -1]],
                    ['$limit' => 80],
                ],
                'tags' => [
                    ['$unwind' => ['path' => '$category_tags', 'preserveNullAndEmptyArrays' => false]],
                    ['$project' => [
                        'tag' => ['$toLower' => ['$trim' => ['input' => ['$toString' => '$category_tags']]]],
                        'activity_weight' => 1,
                    ]],
                    ['$match' => ['tag' => ['$ne' => '']]],
                    ['$group' => [
                        '_id' => '$tag',
                        'count' => ['$sum' => 1],
                        'weight' => ['$sum' => '$activity_weight'],
                    ]],
                    ['$sort' => ['weight' => -1, 'count' => -1]],
                    ['$limit' => 12],
                ],
            ]],
        ];

        try {
            $cursor = $db->user_activities->aggregate($pipeline, ['allowDiskUse' => true]);
            $facets = iterator_to_array($cursor, false);
            $payload = $facets[0] ?? [];
        } catch (\Throwable $e) {
            Log::warning('Recommendation activity summary failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return ['hotels' => [], 'tags' => []];
        }

        return [
            'hotels' => $payload['hotels'] ?? [],
            'tags' => array_values(array_filter(array_map(
                fn ($row) => (string) ($row['_id'] ?? ''),
                $payload['tags'] ?? []
            ))),
        ];
    }

    private function getRecommendationConfig() : array
    {
        $defaults = [
            'minimum_interactions' => 3,
            'time_decay_half_life_days' => 90,
            'action_weights' => [
                'view' => 1.0,
                'click' => 2.0,
                'reservation' => 5.0,
            ],
            'collaborative_weight' => 0.25,
            'collaborative_cache_ttl' => 900,
            'collaborative_similar_users_limit' => 100,
            'collaborative_hotels_limit' => 80,
            'theme_bonus_cap' => 30.0,
            'review_bonus_cap' => 24.0,
        ];

        return array_replace_recursive($defaults, config('recommendation', []));
    }

    private function getCollaborativeScores(object $db, string $userId, array $userHotelIds) : array
    {
        $config = $this->getRecommendationConfig();
        $cacheTtl = max(60, (int) ($config['collaborative_cache_ttl'] ?? 900));
        $cacheKey = sprintf('recommendation:collaborative_scores:%s', $userId);

        return Cache::remember($cacheKey, $cacheTtl, function () use ($db, $userId, $userHotelIds, $config) : array {
            $userHotelIds = array_values(array_filter(array_map(fn ($hotelId) => (string) $hotelId, $userHotelIds)));
            if (empty($userHotelIds)) {
                return [];
            }

            $weights = $config['action_weights'];
            $decayHalfLifeDays = max(1, (int) ($config['time_decay_half_life_days'] ?? 90));

            $similarUsersPipeline = [
                ['$match' => [
                    'user_id' => ['$ne' => $userId],
                    'hotel_id' => ['$in' => $userHotelIds],
                ]],
                ['$addFields' => [
                    'normalized_action' => ['$toLower' => ['$ifNull' => ['$action_type', 'view']]],
                    'base_weight' => [
                        '$switch' => [
                            'branches' => [
                                [
                                    'case' => ['$in' => ['$normalized_action', ['reservation', 'book', 'booking', 'reserved', 'booked']]],
                                    'then' => $weights['reservation'],
                                ],
                                [
                                    'case' => ['$in' => ['$normalized_action', ['click', 'clicked', 'tap']]],
                                    'then' => $weights['click'],
                                ],
                                [
                                    'case' => ['$in' => ['$normalized_action', ['view', 'seen', 'impression', 'page_view']]],
                                    'then' => $weights['view'],
                                ],
                            ],
                            'default' => $weights['view'],
                        ],
                    ],
                    'time_decay' => [
                        '$let' => [
                            'vars' => [
                                'ageMillis' => ['$subtract' => [new \MongoDB\BSON\UTCDateTime(), '$created_at']],
                            ],
                            'in' => [
                                '$pow' => [
                                    0.5,
                                    [
                                        '$divide' => [
                                            ['$divide' => ['$$ageMillis', 1000 * 60 * 60 * 24]],
                                            $decayHalfLifeDays,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]],
                ['$addFields' => [
                    'activity_weight' => ['$multiply' => ['$base_weight', '$time_decay']],
                ]],
                ['$group' => [
                    '_id' => '$user_id',
                    'similarity' => ['$sum' => '$activity_weight'],
                ]],
                ['$sort' => ['similarity' => -1]],
                ['$limit' => max(10, min(100, (int) ($config['collaborative_similar_users_limit'] ?? 100)))],
            ];

            try {
                $cursor = $db->user_activities->aggregate($similarUsersPipeline, ['allowDiskUse' => true]);
                $similarUsers = iterator_to_array($cursor, false);
            } catch (\Throwable $e) {
                Log::warning('Collaborative recommendation failed during similar user search', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }

            $similarUserIds = array_values(array_filter(array_map(fn ($row) => (string) ($row['_id'] ?? ''), $similarUsers)));
            if (empty($similarUserIds)) {
                return [];
            }

            $candidatePipeline = [
                ['$match' => [
                    'user_id' => ['$in' => $similarUserIds],
                    'hotel_id' => ['$nin' => $userHotelIds],
                ]],
                ['$addFields' => [
                    'normalized_action' => ['$toLower' => ['$ifNull' => ['$action_type', 'view']]],
                    'base_weight' => [
                        '$switch' => [
                            'branches' => [
                                [
                                    'case' => ['$in' => ['$normalized_action', ['reservation', 'book', 'booking', 'reserved', 'booked']]],
                                    'then' => $weights['reservation'],
                                ],
                                [
                                    'case' => ['$in' => ['$normalized_action', ['click', 'clicked', 'tap']]],
                                    'then' => $weights['click'],
                                ],
                                [
                                    'case' => ['$in' => ['$normalized_action', ['view', 'seen', 'impression', 'page_view']]],
                                    'then' => $weights['view'],
                                ],
                            ],
                            'default' => $weights['view'],
                        ],
                    ],
                    'time_decay' => [
                        '$let' => [
                            'vars' => [
                                'ageMillis' => ['$subtract' => [new \MongoDB\BSON\UTCDateTime(), '$created_at']],
                            ],
                            'in' => [
                                '$pow' => [
                                    0.5,
                                    [
                                        '$divide' => [
                                            ['$divide' => ['$$ageMillis', 1000 * 60 * 60 * 24]],
                                            $decayHalfLifeDays,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]],
                ['$addFields' => [
                    'activity_weight' => ['$multiply' => ['$base_weight', '$time_decay']],
                ]],
                ['$group' => [
                    '_id' => '$hotel_id',
                    'score' => ['$sum' => '$activity_weight'],
                ]],
                ['$sort' => ['score' => -1]],
                ['$limit' => max(30, min(80, (int) ($config['collaborative_hotels_limit'] ?? 80)))],
            ];

            try {
                $cursor = $db->user_activities->aggregate($candidatePipeline, ['allowDiskUse' => true]);
                $rows = iterator_to_array($cursor, false);
            } catch (\Throwable $e) {
                Log::warning('Collaborative recommendation failed during candidate aggregation', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }

            $scores = [];
            $maxScore = 0.0;
            foreach ($rows as $row) {
                $hotelId = (string) ($row['_id'] ?? '');
                if ($hotelId === '') {
                    continue;
                }

                $score = max(0.0, (float) ($row['score'] ?? 0.0));
                $scores[$hotelId] = $score;
                $maxScore = max($maxScore, $score);
            }

            if ($maxScore > 0.0) {
                foreach ($scores as $hotelId => $score) {
                    $scores[$hotelId] = $score / $maxScore;
                }
            }

            return $scores;
        });
    }

    private function buildThemeSearchTerms(array $tags) : array
    {
        $synonyms = [
            'calme' => ['calme', 'quiet', 'silence', 'serene'],
            'luxe' => ['luxe', 'luxury', 'premium', 'elegant'],
            'famille' => ['famille', 'family', 'kids', 'children'],
            'romantique' => ['romantique', 'romantic', 'couple'],
            'spa' => ['spa', 'wellness', 'relax'],
            'business' => ['business', 'work', 'meeting'],
            'vue mer' => ['vue mer', 'sea view', 'ocean', 'beachfront'],
            'centre ville' => ['centre ville', 'city center', 'downtown', 'central'],
            'piscine' => ['piscine', 'pool', 'swimming'],
            'moderne' => ['moderne', 'modern', 'design'],
        ];

        $terms = [];
        foreach ($tags as $tag) {
            $normalized = $this->normalizeToken($tag);
            if ($normalized === '') {
                continue;
            }

            if (array_key_exists($normalized, $synonyms)) {
                foreach ($synonyms[$normalized] as $term) {
                    $terms[] = $term;
                }
                continue;
            }

            $terms[] = $normalized;
        }

        $terms = array_values(array_unique(array_filter($terms, fn ($term) => trim((string) $term) !== '')));

        return array_slice($terms, 0, 12);
    }

    private function matchThemesFromReviews(object $db, array $searchTerms) : array
    {
        if (empty($searchTerms)) {
            return [];
        }

        $search = implode(' ', $searchTerms);
        if (trim($search) === '') {
            return [];
        }

        try {
            $pipeline = [
                ['$match' => ['$text' => ['$search' => $search]]],
                ['$project' => [
                    'hotelId' => 1,
                    'textScore' => ['$meta' => 'textScore'],
                ]],
                ['$group' => [
                    '_id' => '$hotelId',
                    'score' => ['$sum' => '$textScore'],
                    'mentions' => ['$sum' => 1],
                ]],
                ['$sort' => ['score' => -1, 'mentions' => -1]],
                ['$limit' => 50],
            ];

            $cursor = $db->avis->aggregate($pipeline, ['allowDiskUse' => true]);
            $rows = iterator_to_array($cursor, false);
        } catch (\Throwable $e) {
            Log::warning('Recommendation review text match failed', [
                'error' => $e->getMessage(),
                'search_terms' => $searchTerms,
            ]);
            return [];
        }

        $matches = [];
        foreach ($rows as $row) {
            $hotelId = (string) ($row['_id'] ?? '');
            if ($hotelId === '') {
                continue;
            }

            $matches[$hotelId] = [
                'score' => (float) ($row['score'] ?? 0),
                'mentions' => (int) ($row['mentions'] ?? 0),
            ];
        }

        return $matches;
    }

    private function mergeSignals(array $activityRows, array $reviewMatches, array $topTags, array $collaborativeScores = []) : array
    {
        $config = $this->getRecommendationConfig();
        $collaborativeWeight = (float) ($config['collaborative_weight'] ?? 0.3);
        $ranked = [];

        foreach ($activityRows as $row) {
            $hotelId = (string) ($row['_id'] ?? '');
            if ($hotelId === '') {
                continue;
            }

            $baseScore = (float) ($row['score'] ?? 0);
            $activityScore = $baseScore;
            $reviewScore = isset($reviewMatches[$hotelId]) ? (float) $reviewMatches[$hotelId]['score'] : 0.0;
            $mentions = isset($reviewMatches[$hotelId]) ? (int) $reviewMatches[$hotelId]['mentions'] : 0;
            $themeBonus = min((float) ($config['theme_bonus_cap'] ?? 30.0), $reviewScore * 4.0 + $mentions * 2.0);

            $collaborativeScore = (float) ($collaborativeScores[$hotelId] ?? 0.0);
            $mergedScore = $activityScore + $themeBonus + ($collaborativeScore * $collaborativeWeight);

            $ranked[$hotelId] = [
                'hotel_id' => $hotelId,
                'score' => $mergedScore,
                'activity_score' => $activityScore,
                'review_score' => $reviewScore,
                'collaborative_score' => $collaborativeScore,
                'review_mentions' => $mentions,
                'reservations' => (int) ($row['reservations'] ?? 0),
                'clicks' => (int) ($row['clicks'] ?? 0),
                'views' => (int) ($row['views'] ?? 0),
                'last_activity_at' => $row['last_activity_at'] ?? null,
                'top_tags' => $topTags,
                'source' => 'personalized',
            ];
        }

        foreach ($reviewMatches as $hotelId => $reviewData) {
            if ($hotelId === '') {
                continue;
            }

            if (! array_key_exists($hotelId, $ranked)) {
                $reviewBonus = min((float) ($config['review_bonus_cap'] ?? 24.0), ((float) ($reviewData['score'] ?? 0)) * 4.0 + ((int) ($reviewData['mentions'] ?? 0)) * 2.0);
                $ranked[$hotelId] = [
                    'hotel_id' => $hotelId,
                    'score' => $reviewBonus,
                    'activity_score' => 0.0,
                    'review_score' => (float) ($reviewData['score'] ?? 0),
                    'review_mentions' => (int) ($reviewData['mentions'] ?? 0),
                    'collaborative_score' => (float) ($collaborativeScores[$hotelId] ?? 0.0),
                    'reservations' => 0,
                    'clicks' => 0,
                    'views' => 0,
                    'last_activity_at' => null,
                    'top_tags' => $topTags,
                    'source' => 'theme_match',
                ];
                continue;
            }

            $reviewBonus = min((float) ($config['review_bonus_cap'] ?? 24.0), ((float) ($reviewData['score'] ?? 0)) * 4.0 + ((int) ($reviewData['mentions'] ?? 0)) * 2.0);
            $collaborativeScore = (float) ($collaborativeScores[$hotelId] ?? 0.0);

            $ranked[$hotelId]['score'] += $reviewBonus + ($collaborativeScore * $collaborativeWeight);
            $ranked[$hotelId]['review_score'] = (float) ($reviewData['score'] ?? 0);
            $ranked[$hotelId]['review_mentions'] = (int) ($reviewData['mentions'] ?? 0);
            $ranked[$hotelId]['collaborative_score'] = $collaborativeScore;
        }

        uasort($ranked, function (array $left, array $right) : int {
            $scoreCompare = $right['score'] <=> $left['score'];
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return strcmp((string) ($right['last_activity_at'] ?? ''), (string) ($left['last_activity_at'] ?? ''));
        });

        foreach ($ranked as $hotelId => $candidate) {
            Log::info('Recommendation score trace', [
                'hotel_id' => $hotelId,
                'activity_points' => (float) ($candidate['activity_score'] ?? 0),
                'review_points' => (float) ($candidate['review_score'] ?? 0),
                'collaborative_points' => (float) ($candidate['collaborative_score'] ?? 0),
                'review_mentions' => (int) ($candidate['review_mentions'] ?? 0),
                'total_score' => (float) ($candidate['score'] ?? 0),
                'source' => (string) ($candidate['source'] ?? 'personalized'),
            ]);
        }

        return array_values($ranked);
    }

    private function hydrateHotelsAndFormat(array $rankedCandidates, int $limit) : array
    {
        if ($limit <= 0 || empty($rankedCandidates)) {
            return [];
        }

        $hotelIds = array_slice(array_values(array_unique(array_map(
            fn (array $row) => (string) ($row['hotel_id'] ?? ''),
            $rankedCandidates
        ))), 0, 200);

        $hotelMap = Hotel::query()
            ->whereIn('_id', $hotelIds)
            ->get()
            ->keyBy(fn (Hotel $hotel) => (string) $hotel->_id);

        $items = [];
        foreach ($rankedCandidates as $candidate) {
            if (count($items) >= $limit) {
                break;
            }

            $hotelId = (string) ($candidate['hotel_id'] ?? '');
            if ($hotelId === '') {
                continue;
            }

            $hotel = $hotelMap->get($hotelId);
            if (! $hotel) {
                continue;
            }

            $items[] = [
                'hotel' => $hotel,
                'score' => (float) ($candidate['score'] ?? 0),
                'source' => (string) ($candidate['source'] ?? 'personalized'),
                'signals' => [
                    'activity_score' => (float) ($candidate['activity_score'] ?? 0),
                    'review_score' => (float) ($candidate['review_score'] ?? 0),
                    'collaborative_score' => (float) ($candidate['collaborative_score'] ?? 0),
                    'review_mentions' => (int) ($candidate['review_mentions'] ?? 0),
                    'reservations' => (int) ($candidate['reservations'] ?? 0),
                    'clicks' => (int) ($candidate['clicks'] ?? 0),
                    'views' => (int) ($candidate['views'] ?? 0),
                    'last_activity_at' => $candidate['last_activity_at'] ?? null,
                ],
            ];
        }

        $items = $this->hydratePrices($items);
        return $items;
    }

    /**
     * Hydrate hotels in the result set with their minimum price from the Chambre model.
     */
    private function hydratePrices(array $items) : array
    {
        if (empty($items)) {
            return [];
        }

        $hotelIds = array_map(fn($item) => (string)($item['hotel']->_id ?? ''), $items);
        $hotelIds = array_values(array_unique(array_filter($hotelIds)));

        if (empty($hotelIds)) {
            return $items;
        }

        $minPrices = Chambre::query()
            ->whereIn('hotelId', $hotelIds)
            ->get(['hotelId', 'prix_base'])
            ->groupBy('hotelId')
            ->map(fn($rooms) => $rooms->min('prix_base'));

        foreach ($items as &$item) {
            $hotel = $item['hotel'];
            $hotelId = (string)($hotel->_id ?? '');
            $item['hotel']->prix_min = $minPrices->get($hotelId) ?? 0;
            $item['hotel']->etoiles = max(1, min(5, (int) ($hotel->etoiles ?? 0)));
        }

        return $items;
    }

    private function selectWildcards(array $mainItems, int $wildcardQuota, int $limit) : array
    {
        if ($wildcardQuota <= 0 || $limit <= count($mainItems)) {
            return [];
        }

        $existingIds = [];
        $usedCities = [];
        $usedStarBuckets = [];

        foreach ($mainItems as $item) {
            $hotel = $item['hotel'] ?? null;
            if (! $hotel) {
                continue;
            }

            $existingIds[(string) $hotel->_id] = true;
            $city = $this->normalizeToken((string) ($hotel->ville ?? ''));
            if ($city !== '') {
                $usedCities[$city] = true;
            }

            $usedStarBuckets[(int) ($hotel->etoiles ?? 0)] = true;
        }

        $remainingSlots = max(0, $limit - count($mainItems));
        $pool = Hotel::query()->where('estActif', true)->get();
        $candidates = [];

        foreach ($pool as $hotel) {
            $hotelId = (string) $hotel->_id;
            if ($hotelId === '' || isset($existingIds[$hotelId])) {
                continue;
            }

            $city = $this->normalizeToken((string) ($hotel->ville ?? ''));
            $stars = (int) ($hotel->etoiles ?? 0);

            $noveltyScore = 0;
            if ($city !== '' && ! isset($usedCities[$city])) {
                $noveltyScore += 30;
            }
            if (! isset($usedStarBuckets[$stars])) {
                $noveltyScore += 15;
            }
            if ((float) ($hotel->noteMoyenne ?? 0) > 0) {
                $noveltyScore += min(10, (int) round((float) $hotel->noteMoyenne * 2));
            }

            $candidates[] = [
                'hotel' => $hotel,
                'score' => (float) $noveltyScore,
                'source' => 'wildcard',
                'signals' => [
                    'novelty' => $noveltyScore,
                ],
            ];
        }

        usort($candidates, function (array $left, array $right) : int {
            $scoreCompare = ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            $leftNote = (float) ($left['hotel']->noteMoyenne ?? 0);
            $rightNote = (float) ($right['hotel']->noteMoyenne ?? 0);

            return $rightNote <=> $leftNote;
        });

        $wildcards = array_slice($candidates, 0, min($wildcardQuota, $remainingSlots));

        $wildcards = $this->hydratePrices($wildcards);
        return $wildcards;
    }

    private function fallbackPopularHotels(array $existingItems, int $limit) : array
    {
        if ($limit <= 0) {
            return [];
        }

        $existingIds = [];
        foreach ($existingItems as $item) {
            $hotel = $item['hotel'] ?? null;
            if ($hotel) {
                $existingIds[(string) $hotel->_id] = true;
            }
        }

        $pool = Hotel::query()
            ->where('estActif', true)
            ->get()
            ->sortByDesc(fn (Hotel $hotel) => ((float) ($hotel->noteMoyenne ?? 0) * 10) + ((int) ($hotel->etoiles ?? 0) * 2));

        $items = [];
        foreach ($pool as $hotel) {
            if (count($items) >= $limit) {
                break;
            }

            $hotelId = (string) $hotel->_id;
            if ($hotelId === '' || isset($existingIds[$hotelId])) {
                continue;
            }

            $items[] = [
                'hotel' => $hotel,
                'score' => 0.0,
                'source' => 'fallback',
                'signals' => [
                    'popularity' => (float) ($hotel->noteMoyenne ?? 0),
                ],
            ];
            $existingIds[$hotelId] = true;
        }

        $items = $this->hydratePrices($items);
        return $items;
    }

    private function normalizeToken(string $value) : string
    {
        $value = trim(mb_strtolower($value));
        if ($value === '') {
            return '';
        }

        $value = str_replace(['é', 'è', 'ê', 'ë'], 'e', $value);
        $value = str_replace(['à', 'â', 'ä'], 'a', $value);
        $value = str_replace(['î', 'ï'], 'i', $value);
        $value = str_replace(['ô', 'ö'], 'o', $value);
        $value = str_replace(['ù', 'û', 'ü'], 'u', $value);
        $value = str_replace(['ç'], 'c', $value);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    /**
     * RGPD: Supprime l'historique d'activité de l'utilisateur.
     */
    public function resetUserActivities(string $userId) : void
    {
        $col = $this->mongo->selectDatabase($this->dbName)->user_activities;
        $col->deleteMany(['user_id' => $userId]);

        Log::info('User activities reset (RGPD)', [
            'user_id' => $userId
        ]);
    }

    /**
     * RGPD: Anonymise les logs d'activité vieux de plus de X mois.
     */
    public function anonymizeOldActivities(int $monthsAgo = 6) : int
    {
        $col = $this->mongo->selectDatabase($this->dbName)->user_activities;
        
        // Calculer la date limite (UTC)
        $dateLimit = new \DateTime();
        $dateLimit->modify("-{$monthsAgo} months");
        $bsonDate = new \MongoDB\BSON\UTCDateTime($dateLimit);

        $result = $col->updateMany(
            [
                'created_at' => ['$lt' => $bsonDate],
                'user_id' => ['$ne' => null]
            ],
            [
                '$set' => [
                    'user_id' => null,
                    'session_id' => 'anonymized'
                ]
            ]
        );

        return $result->getModifiedCount();
    }
}
