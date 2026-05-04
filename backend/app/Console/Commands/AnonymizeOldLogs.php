<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RecommendationService;

class AnonymizeOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:anonymize {--months=6 : Nombre de mois avant anonymisation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Anonymise les logs d\'activité vieux de plus de X mois pour la conformité RGPD';

    /**
     * Execute the console command.
     */
    public function handle(RecommendationService $recommendationService)
    {
        $months = (int) $this->option('months');
        $this->info("Anonymisation des logs plus vieux de {$months} mois en cours...");

        $modifiedCount = $recommendationService->anonymizeOldActivities($months);

        $this->info("Anonymisation terminée : {$modifiedCount} enregistrements traités.");
    }
}
