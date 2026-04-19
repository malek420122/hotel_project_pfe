<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Hotel extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'hotels';

    private const REAL_HOTEL_PHOTOS_BY_NAME = [
        'the ritz london' => [
            'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800',
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1400&q=80',
        ],
        'burj al arab' => [
            'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=800',
            'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
        ],
        'hotel negresco' => [
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800',
            'https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1489515217757-5fd1be406fef?auto=format&fit=crop&w=1400&q=80',
        ],
        'hotel plaza athenee' => [
            'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800',
            'https://images.unsplash.com/photo-1431274172761-fca41d930114?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=1400&q=80',
        ],
        'hotel ease demo hotel' => [
            'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
            'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
        ],
    ];

    private const REAL_HOTEL_PHOTOS_FALLBACK = [
        'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1400&q=80',
        'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?auto=format&fit=crop&w=1400&q=80',
    ];

    protected $fillable = [
        'nom', 'description', 'adresse', 'ville',
        'latitude', 'longitude', 'etoiles', 'photos',
        'noteMoyenne', 'equipements', 'services', 'estActif'
    ];

    protected $attributes = [
        'etoiles' => 3,
        'photos' => [],
        'equipements' => [],
        'services' => [],
        'noteMoyenne' => 0,
        'estActif' => true,
    ];

    protected $appends = ['previewPhoto'];

    public function getPhotosAttribute($value): array
    {
        $photos = is_array($value) ? array_values(array_filter($value, fn ($p) => is_string($p) && trim($p) !== '')) : [];
        if (! empty($photos)) {
            return $photos;
        }

        $key = $this->normalizeHotelName((string) ($this->attributes['nom'] ?? ''));

        return self::REAL_HOTEL_PHOTOS_BY_NAME[$key] ?? self::REAL_HOTEL_PHOTOS_FALLBACK;
    }

    public function getPreviewPhotoAttribute(): ?string
    {
        $key = $this->normalizeHotelName((string) ($this->attributes['nom'] ?? ''));
        if (isset(self::REAL_HOTEL_PHOTOS_BY_NAME[$key]) && ! empty(self::REAL_HOTEL_PHOTOS_BY_NAME[$key])) {
            return (string) self::REAL_HOTEL_PHOTOS_BY_NAME[$key][0];
        }

        $photos = $this->photos;
        if (! empty($photos)) {
            return (string) $photos[0];
        }

        $index = $this->seedIndex((string) ($this->attributes['_id'] ?? $this->attributes['nom'] ?? ''), count(self::REAL_HOTEL_PHOTOS_FALLBACK));

        return self::REAL_HOTEL_PHOTOS_FALLBACK[$index] ?? null;
    }

    public function setEtoilesAttribute($value): void
    {
        $stars = (int) $value;
        $this->attributes['etoiles'] = max(1, min(5, $stars));
    }

    private function normalizeHotelName(string $name): string
    {
        $name = trim(mb_strtolower($name));
        $name = str_replace(['é', 'è', 'ê', 'ë'], 'e', $name);
        $name = str_replace(['à', 'â', 'ä'], 'a', $name);
        $name = str_replace(['î', 'ï'], 'i', $name);
        $name = str_replace(['ô', 'ö'], 'o', $name);
        $name = str_replace(['ù', 'û', 'ü'], 'u', $name);
        $name = str_replace(['ç'], 'c', $name);

        return preg_replace('/\s+/', ' ', $name) ?? $name;
    }

    private function seedIndex(string $seed, int $length): int
    {
        if ($length <= 0) {
            return 0;
        }

        $hash = crc32($seed);
        if ($hash < 0) {
            $hash = $hash * -1;
        }

        return $hash % $length;
    }
}
