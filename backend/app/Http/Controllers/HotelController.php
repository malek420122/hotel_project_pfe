<?php
namespace App\Http\Controllers;
use App\Models\Hotel;
use App\Models\Chambre;
use App\Models\Avis;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use MongoDB\BSON\ObjectId;
use Tymon\JWTAuth\Facades\JWTAuth;
class HotelController extends Controller
{
    public function homepageStats()
    {
        $hotelsQuery = Hotel::query()->where('estActif', true);
        $hotelsCount = (int) $hotelsQuery->count();
        $citiesCount = (int) $hotelsQuery->clone()->get(['ville'])
            ->map(fn ($hotel) => $this->localizeField($hotel->ville ?? null, 'fr'))
            ->filter(fn ($city) => is_string($city) && trim($city) !== '')
            ->unique()
            ->count();
        $clientsCount = (int) User::query()->where('role', 'client')->where('est_actif', true)->count();
        $reservationsCount = (int) Reservation::query()->count();
        $reviewsAvg = Avis::query()->where('statut', 'PUBLIE')->avg('note');
        $hotelAvg = Hotel::query()->where('estActif', true)->where('noteMoyenne', '>', 0)->avg('noteMoyenne');

        $avgSource = $reviewsAvg ?? $hotelAvg;
        $avgRating = $avgSource !== null ? round((float) $avgSource, 1) : null;

        return response()->json([
            'hotels_count' => $hotelsCount,
            'clients_count' => $clientsCount,
            'reservations_count' => $reservationsCount,
            'cities_count' => $citiesCount,
            'average_rating' => $avgRating,
        ]);
    }

    public function cities(Request $request)
    {
        $lang = $this->resolveLanguage($request);

        $hotels = Hotel::query()
            ->where('estActif', true)
            ->get(['ville', 'photos', '_id']);

        $grouped = [];

        foreach ($hotels as $hotel) {
            $city = trim($this->localizeField($hotel->ville ?? null, $lang));
            if ($city === '') {
                continue;
            }

            if (! isset($grouped[$city])) {
                $grouped[$city] = [
                    'ville' => $city,
                    'count' => 0,
                    'image' => null,
                ];
            }

            $grouped[$city]['count']++;

            if (! $grouped[$city]['image']) {
                $photos = is_array($hotel->photos ?? null) ? array_values(array_filter($hotel->photos)) : [];
                if (! empty($photos)) {
                    $grouped[$city]['image'] = $photos[0];
                }
            }
        }

        $fallbackImage = 'https://images.unsplash.com/photo-1505761671935-60b3a7427bad?auto=format&fit=crop&w=1400&q=80';

        $payload = collect(array_values($grouped))
            ->sortByDesc('count')
            ->values()
            ->map(function (array $city) use ($fallbackImage) {
                $city['image'] = $city['image'] ?: $fallbackImage;
                return $city;
            });

        return response()->json($payload->all());
    }

    public function suggestions(Request $request)
    {
        $lang = $this->resolveLanguage($request);
        $q = trim((string) $request->input('q', ''));
        if ($q === '') {
            return response()->json([]);
        }

        $needle = $this->normalizeText($q);
        if ($needle === '') {
            return response()->json([]);
        }

        $hotels = Hotel::query()
            ->where('estActif', true)
            ->get(['nom', 'ville']);

        $indexed = [];

        foreach ($hotels as $hotel) {
            $cityValues = $this->extractLocalizedStringVariants($hotel->ville ?? null, $lang);
            $city = trim((string) ($cityValues[0] ?? ''));
            $cityNorm = '';
            $cityPos = false;
            foreach ($cityValues as $cityCandidate) {
                $cityNormCandidate = $this->normalizeText($cityCandidate);
                $cityPosCandidate = mb_strpos($cityNormCandidate, $needle);
                if ($cityPosCandidate !== false) {
                    $cityNorm = $cityNormCandidate;
                    $cityPos = $cityPosCandidate;
                    $key = 'city|' . $cityNormCandidate;
                    $score = $cityPosCandidate === 0 ? 0 : 1;
                    if (! isset($indexed[$key]) || $score < $indexed[$key]['score']) {
                        $indexed[$key] = [
                            'type' => 'ville',
                            'ville' => $city,
                            'value' => $city,
                            'label' => 'Ville: ' . $city,
                            'score' => $score,
                            'norm' => $cityNormCandidate,
                        ];
                    }

                    break;
                }
            }

            $name = trim((string) ($hotel->nom ?? ''));
            if ($name !== '') {
                $nameNorm = $this->normalizeText($name);
                $namePos = mb_strpos($nameNorm, $needle);

                // Include hotels matching by name OR by their city, so "Par" can suggest
                // both "Paris" and related hotels like "Hôtel Plaza Athénée".
                if ($namePos !== false || $cityPos !== false) {
                    $key = 'hotel|' . $nameNorm;
                    $bestPos = $namePos !== false ? $namePos : $cityPos;
                    $score = $bestPos === 0 ? 0 : 1;
                    if (! isset($indexed[$key]) || $score < $indexed[$key]['score']) {
                        $indexed[$key] = [
                            'type' => 'hotel',
                            'nom' => $name,
                            'value' => $name,
                            'label' => 'Hôtel: ' . $name,
                            'score' => $score,
                            'norm' => $nameNorm,
                        ];
                    }
                }
            }
        }

        $suggestions = collect(array_values($indexed))
            ->sort(function (array $a, array $b) {
                if ($a['score'] !== $b['score']) {
                    return $a['score'] <=> $b['score'];
                }

                return strcmp($a['norm'], $b['norm']);
            })
            ->values()
            ->map(function (array $item) {
                if (($item['type'] ?? '') === 'hotel') {
                    return [
                        'type' => 'hotel',
                        'nom' => $item['nom'] ?? $item['value'] ?? '',
                        'value' => $item['value'] ?? $item['nom'] ?? '',
                        'label' => $item['label'] ?? '',
                    ];
                }

                return [
                    'type' => 'ville',
                    'ville' => $item['ville'] ?? $item['value'] ?? '',
                    'value' => $item['value'] ?? $item['ville'] ?? '',
                    'label' => $item['label'] ?? '',
                ];
            })
            ->all();

        return response()->json($suggestions);
    }

    public function priceRange()
    {
        $cacheKey = 'hotels:price-range';

        $range = Cache::remember($cacheKey, 60, function () {
            $prices = Chambre::query()
                ->pluck('prix_base')
                ->filter(fn ($value) => is_numeric($value))
                ->map(fn ($value) => (float) $value)
                ->values();

            if ($prices->isEmpty()) {
                return [
                    'min' => 0,
                    'max' => 0,
                ];
            }

            return [
                'min' => (int) floor((float) $prices->min()),
                'max' => (int) ceil((float) $prices->max()),
            ];
        });

        return response()->json($range);
    }

    public function index(Request $request)
    {
        $lang = $this->resolveLanguage($request);
        $isAdminContext = $this->isAdminContext($request);
        $filters = $this->normalizeHotelFilters($request);
        $sort = $this->normalizeSort((string) $request->input('sort', 'recommande'));
        $strictAvailability = $request->boolean('strict_availability', false);
        $perPage = (int) ($request->input('per_page', 10));
        if ($perPage <= 0) {
            $perPage = 10;
        }

        $cacheKey = 'hotels:index:' . md5(json_encode([
            'admin' => $isAdminContext,
            'filters' => $filters,
            'sort' => $sort,
            'strict_availability' => $strictAvailability,
            'lang' => $lang,
            'per_page' => $perPage,
            'page' => (int) $request->input('page', 1),
        ]));

        $hotels = Cache::remember($cacheKey, 60, function () use ($filters, $sort, $isAdminContext, $perPage, $strictAvailability, $request, $lang) {
            $query = Hotel::query();

            if (! $isAdminContext) {
                $query->where('estActif', true);
            }

            if ($filters['etoiles_min'] !== null) {
                $query->where('etoiles', '>=', $filters['etoiles_min']);
            } elseif (! empty($filters['etoiles'])) {
                if (count($filters['etoiles']) === 1) {
                    $query->where('etoiles', '=', $filters['etoiles'][0]);
                } else {
                    $query->whereIn('etoiles', $filters['etoiles']);
                }
            }

            if (
                $strictAvailability
                &&
                (
                $filters['prix_min'] !== null
                || $filters['prix_max'] !== null
                || ($filters['date_arrivee'] !== null && $filters['date_depart'] !== null)
                )
            ) {
                $availableRooms = Chambre::query()->where('estDisponible', true);

                if ($filters['prix_min'] !== null) {
                    $availableRooms->where('prix_base', '>=', $filters['prix_min']);
                }

                if ($filters['prix_max'] !== null) {
                    $availableRooms->where('prix_base', '<=', $filters['prix_max']);
                }

                if ($filters['nb_voyageurs'] !== null) {
                    $availableRooms->where('maxVoyageurs', '>=', $filters['nb_voyageurs']);
                }

                if ($filters['date_arrivee'] !== null && $filters['date_depart'] !== null) {
                    $reservedChambreIds = Reservation::query()
                        ->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'EN_ATTENTE'])
                        ->where(function ($q) use ($filters) {
                            $q->where('dateArrivee', '<', $filters['date_depart'])
                                ->where('dateDepart', '>', $filters['date_arrivee']);
                        })
                        ->pluck('chambreId')
                        ->filter()
                        ->values()
                        ->all();

                    if (! empty($reservedChambreIds)) {
                        $availableRooms->whereNotIn('_id', $reservedChambreIds);
                    }
                }

                $availableHotelIds = collect($availableRooms->pluck('hotelId')->all())
                    ->filter()
                    ->unique()
                    ->values();

                if ($availableHotelIds->isEmpty()) {
                    $query->whereRaw(['_id' => ['$in' => []]]);
                } else {
                    $stringIds = $availableHotelIds->map(fn ($id) => (string) $id)->all();
                    $objectIds = $availableHotelIds->map(function ($id) {
                        $value = (string) $id;
                        if (strlen($value) !== 24) {
                            return null;
                        }

                        try {
                            return new ObjectId($value);
                        } catch (\Exception) {
                            return null;
                        }
                    })->filter()->values()->all();

                    $query->where(function ($q) use ($stringIds, $objectIds) {
                        $q->whereIn('_id', $stringIds);
                        if (! empty($objectIds)) {
                            $q->orWhereIn('_id', $objectIds);
                        }
                    });
                }
            }

            $hotelsCollection = $query->get();

            if ($filters['ville'] !== '') {
                $searchTerm = $filters['ville'];
                $hotelsCollection = $hotelsCollection
                    ->filter(fn ($hotel) => $this->hotelMatchesCity($hotel, $searchTerm, $lang))
                    ->values();
            }

            if ($filters['search'] !== '') {
                $searchTerm = $filters['search'];
                $hotelsCollection = $hotelsCollection
                    ->filter(fn ($hotel) => $this->hotelMatchesSearch($hotel, $searchTerm, $lang))
                    ->values();
            }

            if (! empty($filters['equipements'])) {
                $requiredServices = collect($filters['equipements'])->map(fn ($value) => trim(strtolower((string) $value)))->filter()->unique()->values()->all();
                $hotelsCollection = $hotelsCollection->filter(function ($hotel) use ($requiredServices) {
                    $services = $this->extractHotelServices($hotel);
                    return empty(array_diff($requiredServices, $services));
                })->values();
            }

            if ($sort === 'prix_asc' || $sort === 'prix_desc') {
                $sortedHotels = $this->sortHotelsByPrice($hotelsCollection, $sort);

                return $this->paginateCollection(
                    $sortedHotels,
                    $perPage,
                    (int) $request->input('page', 1),
                    [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );
            }

            if ($sort === 'note') {
                $sortedHotels = $hotelsCollection->sort(function ($a, $b) {
                    $ratingA = (float) ($a->noteMoyenne ?? 0);
                    $ratingB = (float) ($b->noteMoyenne ?? 0);

                    if ($ratingA === $ratingB) {
                        return strcmp((string) ($b->nom ?? ''), (string) ($a->nom ?? ''));
                    }

                    return $ratingB <=> $ratingA;
                })->values();

                return $this->paginateCollection(
                    $sortedHotels,
                    $perPage,
                    (int) $request->input('page', 1),
                    [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );
            }

            $sortedHotels = $hotelsCollection->sort(function ($a, $b) {
                $starsA = (int) ($a->etoiles ?? 0);
                $starsB = (int) ($b->etoiles ?? 0);
                if ($starsA === $starsB) {
                    $ratingA = (float) ($a->noteMoyenne ?? 0);
                    $ratingB = (float) ($b->noteMoyenne ?? 0);
                    if ($ratingA === $ratingB) {
                        return strcmp((string) ($a->nom ?? ''), (string) ($b->nom ?? ''));
                    }

                    return $ratingB <=> $ratingA;
                }

                return $starsB <=> $starsA;
            })->values();

            return $this->paginateCollection(
                $sortedHotels,
                $perPage,
                (int) $request->input('page', 1),
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        });

        $hotels->setCollection($this->hydrateHotelCards($hotels->getCollection()));
        $hotels->setCollection(
            $hotels->getCollection()->map(fn ($hotel) => $this->flattenLocalizedHotelFields($hotel, $lang))
        );

        return response()->json($hotels);
    }

    public function show(Request $request, $id)
    {
        $lang = $this->resolveLanguage($request);
        $hotel = Hotel::findOrFail($id);
        $hotel = $this->hydrateHotelEntity($hotel);
        $hotel = $this->flattenLocalizedHotelFields($hotel, $lang);

        return response()->json($hotel);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string',
            'adresse' => 'required|string',
            'ville' => 'required|string',
            'etoiles' => 'required|integer|between:1,5',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $hotel = Hotel::create($request->all());
        return response()->json($hotel, 201);
    }

    public function update(Request $request, $id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update($request->all());
        return response()->json($hotel);
    }
    public function destroy($id)
    {
        Hotel::findOrFail($id)->delete();
        return response()->json(['message' => 'Hôtel supprimé']);
    }

    public function chambresDisponibles(Request $request, $id)
    {
        $query = Chambre::where('hotelId', $id)->where('estDisponible', true);

        if ($request->has('dateArrivee') && $request->has('dateDepart')) {
            $dateArrivee = $request->dateArrivee;
            $dateDepart = $request->dateDepart;

            $reservedIds = Reservation::where('hotelId', $id)
                ->whereIn('statut', ['CONFIRMEE', 'EN_COURS', 'EN_ATTENTE'])
                ->where(function ($q) use ($dateArrivee, $dateDepart) {
                    $q->where('dateArrivee', '<', $dateDepart)
                      ->where('dateDepart', '>', $dateArrivee);
                })
                ->pluck('chambreId')
                ->toArray();

            $query->whereNotIn('_id', $reservedIds);
        }

        return response()->json($query->get());
    }

    public function avis($id)
    {
        $avis = Avis::where('hotelId', $id)
            ->where('statut', 'PUBLIE')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (Avis $review) {
                $client = User::find($review->clientId);

                return [
                    '_id' => (string) $review->_id,
                    'hotelId' => (string) ($review->hotelId ?? ''),
                    'reservationId' => (string) ($review->reservationId ?? ''),
                    'note' => (int) ($review->note ?? 0),
                    'commentaire' => (string) ($review->commentaire ?? ''),
                    'statut' => (string) ($review->statut ?? 'PUBLIE'),
                    'createdAt' => $review->created_at,
                    'client' => $client ? [
                        '_id' => (string) $client->_id,
                        'prenom' => (string) ($client->prenom ?? ''),
                        'nom' => (string) ($client->nom ?? ''),
                    ] : null,
                ];
            })
            ->values();

        return response()->json($avis);
    }

    public function toggle($id)
    {
        $hotel = Hotel::findOrFail($id);
        $hotel->update(['estActif' => !$hotel->estActif]);
        return response()->json($hotel);
    }

    private function isAdminContext(Request $request): bool
    {
        if (!$request->bearerToken()) {
            return false;
        }

        try {
            $user = JWTAuth::parseToken()->authenticate();
            return $user?->role === 'admin' && $user?->est_actif;
        } catch (\Exception) {
            return false;
        }
    }

    private function normalizeHotelFilters(Request $request): array
    {
        $ville = trim((string) $request->input('ville', ''));
        $search = trim((string) $request->input('search', ''));

        $etoilesRaw = $request->input('etoiles', []);
        if (! is_array($etoilesRaw)) {
            $etoilesRaw = [$etoilesRaw];
        }

        $etoiles = collect($etoilesRaw)
            ->flatMap(function ($value) {
                if (is_array($value)) {
                    return $value;
                }

                return [$value];
            })
            ->filter(function ($value) {
                return $value !== '' && $value !== null;
            })
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value >= 1 && $value <= 5)
            ->values()
            ->all();

        $prixMin = $request->filled('prix_min') ? (float) $request->input('prix_min') : null;
        $prixMax = $request->filled('prix_max') ? (float) $request->input('prix_max') : null;
        $etoilesMin = $request->filled('etoiles_min') ? (int) $request->input('etoiles_min') : null;
        $dateArrivee = $request->filled('dateArrivee') ? (string) $request->input('dateArrivee') : null;
        $dateDepart = $request->filled('dateDepart') ? (string) $request->input('dateDepart') : null;
        $nbVoyageurs = $request->filled('nbVoyageurs') ? (int) $request->input('nbVoyageurs') : null;
        $allowedEquipements = ['spa', 'piscine', 'wifi', 'parking', 'climatisation', 'restaurant'];

        $servicesInput = $request->input('services', $request->input('equipements', []));
        if (! is_array($servicesInput)) {
            $servicesInput = [$servicesInput];
        }

        $equipements = collect($servicesInput)
            ->flatMap(function ($value) {
                if (is_array($value)) {
                    return $value;
                }

                return explode(',', (string) $value);
            })
            ->map(fn ($value) => trim(strtolower((string) $value)))
            ->filter(fn ($value) => $value !== '' && in_array($value, $allowedEquipements, true))
            ->unique()
            ->values()
            ->all();

        return [
            'ville' => $ville,
            'search' => $search,
            'etoiles' => $etoiles,
            'equipements' => $equipements,
            'prix_min' => $prixMin,
            'prix_max' => $prixMax,
            'etoiles_min' => $etoilesMin,
            'date_arrivee' => $dateArrivee,
            'date_depart' => $dateDepart,
            'nb_voyageurs' => $nbVoyageurs,
        ];
    }

    private function extractHotelServices($hotel): array
    {
        $services = $hotel->services ?? $hotel->equipements ?? [];
        if (! is_array($services)) {
            $services = explode(',', (string) $services);
        }

        return collect($services)
            ->map(fn ($value) => trim(strtolower((string) $value)))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeSort(string $sort): string
    {
        $allowed = ['recommande', 'prix_asc', 'prix_desc', 'note'];

        return in_array($sort, $allowed, true) ? $sort : 'recommande';
    }

    private function sortHotelsByPrice($hotels, string $sort)
    {
        $hotelCollection = collect($hotels)->values();
        if ($hotelCollection->isEmpty()) {
            return $hotelCollection;
        }

        $hotelIds = $hotelCollection
            ->map(fn ($hotel) => (string) ($hotel->_id ?? ''))
            ->filter()
            ->values()
            ->all();

        $priceByHotelId = [];
        $rooms = Chambre::query()
            ->whereIn('hotelId', $hotelIds)
            ->get(['hotelId', 'prix_base']);

        foreach ($rooms as $room) {
            $hotelId = (string) ($room->hotelId ?? '');
            $price = is_numeric($room->prix_base) ? (float) $room->prix_base : null;
            if ($hotelId === '' || $price === null) {
                continue;
            }

            if (! array_key_exists($hotelId, $priceByHotelId) || $price < $priceByHotelId[$hotelId]) {
                $priceByHotelId[$hotelId] = $price;
            }
        }

        return $hotelCollection->sort(function ($a, $b) use ($sort, $priceByHotelId) {
            $aId = (string) ($a->_id ?? '');
            $bId = (string) ($b->_id ?? '');
            $aPrice = $priceByHotelId[$aId] ?? null;
            $bPrice = $priceByHotelId[$bId] ?? null;

            if ($aPrice === null && $bPrice === null) {
                return 0;
            }
            if ($aPrice === null) {
                return 1;
            }
            if ($bPrice === null) {
                return -1;
            }

            return $sort === 'prix_desc'
                ? $bPrice <=> $aPrice
                : $aPrice <=> $bPrice;
        })->values();
    }

    private function paginateCollection($items, int $perPage, int $page, array $options = []): LengthAwarePaginator
    {
        $collection = collect($items)->values();
        $total = $collection->count();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $slice = $collection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator($slice, $total, $perPage, $page, $options);
    }

    private function countryToCities(string $input): array
    {
        $normalized = $this->normalizeText($input);

        $mapping = [
            'france' => ['Paris', 'Nice', 'Cannes', 'Lyon', 'Marseille'],
            'maroc' => ['Marrakech', 'Casablanca', 'Rabat', 'Fes', 'Agadir', 'Tangier'],
            'morocco' => ['Marrakech', 'Casablanca', 'Rabat', 'Fes', 'Agadir', 'Tangier'],
            'uk' => ['London', 'Londres', 'Manchester', 'Liverpool', 'Birmingham'],
            'united kingdom' => ['London', 'Londres', 'Manchester', 'Liverpool', 'Birmingham'],
            'england' => ['London', 'Londres', 'Manchester', 'Liverpool', 'Birmingham'],
            'uae' => ['Dubai', 'Dubaï', 'Abu Dhabi', 'Sharjah'],
            'united arab emirates' => ['Dubai', 'Dubaï', 'Abu Dhabi', 'Sharjah'],
            'emirats arabes unis' => ['Dubai', 'Dubaï', 'Abu Dhabi', 'Sharjah'],
        ];

        return $mapping[$normalized] ?? [];
    }

    private function cityAliases(string $input): array
    {
        $normalized = $this->normalizeText($input);

        $mapping = [
            'dubai' => ['Dubai', 'Dubaï'],
            'dubai marina' => ['Dubai', 'Dubaï'],
            'dubai city' => ['Dubai', 'Dubaï'],
            'dubai uae' => ['Dubai', 'Dubaï'],
            'dubai emirates' => ['Dubai', 'Dubaï'],
            'dubai emirats' => ['Dubai', 'Dubaï'],
            'dubai united arab emirates' => ['Dubai', 'Dubaï'],
            'dubai emirats arabes unis' => ['Dubai', 'Dubaï'],
            'dubai emirats unis' => ['Dubai', 'Dubaï'],
            'dubai emirats arabes' => ['Dubai', 'Dubaï'],
            'dubaï' => ['Dubai', 'Dubaï'],
            'london' => ['London', 'Londres'],
            'londres' => ['London', 'Londres'],
        ];

        return $mapping[$normalized] ?? [];
    }

    private function normalizeText(string $value): string
    {
        $value = trim(mb_strtolower($value));
        $value = str_replace(['é', 'è', 'ê', 'ë'], 'e', $value);
        $value = str_replace(['à', 'â', 'ä'], 'a', $value);
        $value = str_replace(['î', 'ï'], 'i', $value);
        $value = str_replace(['ô', 'ö'], 'o', $value);
        $value = str_replace(['ù', 'û', 'ü'], 'u', $value);
        $value = str_replace(['ç'], 'c', $value);

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function resolveLanguage(Request $request): string
    {
        $header = strtolower(trim((string) $request->header('Accept-Language', 'fr')));
        $lang = substr($header, 0, 2);

        return in_array($lang, ['fr', 'en', 'ar'], true) ? $lang : 'fr';
    }

    private function localizeField($value, string $lang): string
    {
        if (is_array($value)) {
            $localized = $value[$lang] ?? $value['fr'] ?? reset($value);
            return is_string($localized) ? $localized : '';
        }

        return is_string($value) ? $value : '';
    }

    private function extractLocalizedStringVariants($value, string $lang): array
    {
        if (! is_array($value)) {
            $text = trim((string) $value);
            return $text === '' ? [] : [$text];
        }

        $variants = [];
        $preferred = $this->localizeField($value, $lang);
        if ($preferred !== '') {
            $variants[] = $preferred;
        }

        foreach (['fr', 'en', 'ar'] as $locale) {
            $candidate = trim((string) ($value[$locale] ?? ''));
            if ($candidate !== '' && ! in_array($candidate, $variants, true)) {
                $variants[] = $candidate;
            }
        }

        return $variants;
    }

    private function flattenLocalizedHotelFields($hotel, string $lang)
    {
        $hotel->description = $this->localizeField($hotel->description ?? null, $lang);
        $hotel->ville = $this->localizeField($hotel->ville ?? null, $lang);

        return $hotel;
    }

    private function hotelMatchesSearch($hotel, string $searchTerm, string $lang): bool
    {
        $needles = $this->expandSearchNeedles($searchTerm);
        if (empty($needles)) {
            return true;
        }

        $cityCandidates = $this->extractLocalizedStringVariants($hotel->ville ?? null, $lang);
        $name = (string) ($hotel->nom ?? '');
        $address = (string) ($hotel->adresse ?? '');

        $candidates = array_merge($cityCandidates, [$name, $address]);

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            $normalizedCandidate = $this->normalizeText((string) $candidate);
            foreach ($needles as $needle) {
                if (mb_strpos($normalizedCandidate, $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hotelMatchesCity($hotel, string $searchTerm, string $lang): bool
    {
        $needles = $this->expandSearchNeedles($searchTerm);
        if (empty($needles)) {
            return true;
        }

        $cityCandidates = $this->extractLocalizedStringVariants($hotel->ville ?? null, $lang);

        foreach ($cityCandidates as $candidate) {
            $normalizedCandidate = $this->normalizeText((string) $candidate);
            foreach ($needles as $needle) {
                if (mb_strpos($normalizedCandidate, $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function expandSearchNeedles(string $input): array
    {
        $variants = array_merge(
            [$input],
            $this->countryToCities($input),
            $this->cityAliases($input)
        );

        return collect($variants)
            ->map(fn ($value) => $this->normalizeText((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function hydrateHotelCards($hotels)
    {
        $hotelCollection = collect($hotels);
        $hotelIds = $hotelCollection
            ->map(fn ($hotel) => (string) ($hotel->_id ?? ''))
            ->filter()
            ->values()
            ->all();

        $priceByHotelId = [];
        if (! empty($hotelIds)) {
            $rooms = Chambre::query()
                ->whereIn('hotelId', $hotelIds)
                ->get(['hotelId', 'prix_base']);

            foreach ($rooms as $room) {
                $hotelId = (string) ($room->hotelId ?? '');
                $price = is_numeric($room->prix_base) ? (float) $room->prix_base : null;
                if ($hotelId === '' || $price === null) {
                    continue;
                }

                if (! array_key_exists($hotelId, $priceByHotelId) || $price < $priceByHotelId[$hotelId]) {
                    $priceByHotelId[$hotelId] = $price;
                }
            }
        }

        return $hotelCollection->map(function ($hotel) use ($priceByHotelId) {
            $hotelId = (string) ($hotel->_id ?? '');
            $hotel->prix_min = array_key_exists($hotelId, $priceByHotelId)
                ? round((float) $priceByHotelId[$hotelId], 2)
                : null;
            $hotel->etoiles = max(1, min(5, (int) ($hotel->etoiles ?? 0)));

            return $hotel;
        });
    }

    private function hydrateHotelEntity(Hotel $hotel): Hotel
    {
        $prices = Chambre::query()
            ->where('hotelId', (string) $hotel->_id)
            ->pluck('prix_base')
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (float) $value)
            ->values();

        $hotel->prix_min = $prices->isEmpty() ? null : round((float) $prices->min(), 2);
        $hotel->etoiles = max(1, min(5, (int) ($hotel->etoiles ?? 0)));

        return $hotel;
    }
}
