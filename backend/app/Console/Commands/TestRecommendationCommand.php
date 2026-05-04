<?php

namespace App\Console\Commands;

use App\Services\RecommendationService;
use Illuminate\Console\Command;

class TestRecommendationCommand extends Command
{
    protected $signature = 'test:recommendation {userId : MongoDB user id to test} {--limit=10 : Number of hotels to display}';
    protected $description = 'Print the personalized recommendation feed for a user';

    public function handle(RecommendationService $recommendationService): int
    {
        $userId = (string) $this->argument('userId');
        $limit = max(1, (int) $this->option('limit'));

        $recommendations = $recommendationService->getPersonalizedFeed($userId, $limit);

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
    }
}
