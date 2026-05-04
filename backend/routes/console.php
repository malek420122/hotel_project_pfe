<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\CreateMongoIndexes;
use App\Services\RecommendationService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mongo:create-indexes', function () {
    return app(CreateMongoIndexes::class)->handle();
})->purpose('Create MongoDB text and activity indexes');

Artisan::command('test:recommendation {userId} {--limit=10}', function () {
    $userId = (string) $this->argument('userId');
    $limit = max(1, (int) $this->option('limit'));
    $recommendations = app(RecommendationService::class)->getPersonalizedFeed($userId, $limit);

    $this->info('Test de recommandation pour l\'utilisateur: ' . $userId);
    $this->line('');

    if (empty($recommendations)) {
        $this->warn('Aucun hôtel recommandé pour cet utilisateur.');
        return self::SUCCESS;
    }

    foreach ($recommendations as $index => $item) {
        $hotel = $item['hotel'] ?? null;
        if (! $hotel) {
            continue;
        }

        $name = (string) ($hotel->nom ?? $hotel->name ?? 'Hôtel inconnu');
        $score = number_format((float) ($item['score'] ?? 0), 2, '.', '');
        $source = (string) ($item['source'] ?? 'personalized');
        $city = (string) ($hotel->ville ?? '');
        $stars = (int) ($hotel->etoiles ?? 0);

        $this->line(sprintf(
            '%02d. %s | Score: %s | Source: %s | Ville: %s | Étoiles: %d',
            $index + 1,
            $name,
            $score,
            $source,
            $city,
            $stars
        ));
    }

    return self::SUCCESS;
})->purpose('Print a personalized recommendation feed');

Schedule::command('app:send-stay-reminders')->dailyAt('08:00');
Schedule::command('logs:anonymize')->monthly();
