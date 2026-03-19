<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Reservation;
use App\Models\Notification;
use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-automated', function () {
    $tomorrow = Carbon::tomorrow()->toDateString();

    $reservations = Reservation::where('statut', 'CONFIRMEE')
        ->whereDate('dateArrivee', $tomorrow)
        ->get();

    foreach ($reservations as $reservation) {
        Notification::create([
            'userId' => $reservation->clientId,
            'type' => 'RAPPEL_SEJOUR',
            'message' => "Rappel: votre sejour {$reservation->reference} commence demain.",
            'estLue' => false,
        ]);
    }

    $activePromo = Promotion::where('estActive', true)
        ->where('dateDebut', '<=', now())
        ->where('dateFin', '>=', now())
        ->orderBy('remise_pourcent', 'desc')
        ->first();

    if ($activePromo) {
        $userIdsNotifiedToday = Notification::where('type', 'OFFRE_SPECIALE')
            ->whereDate('created_at', Carbon::today()->toDateString())
            ->pluck('userId')
            ->toArray();

        $users = User::where('role', 'client')
            ->whereNotIn('_id', $userIdsNotifiedToday)
            ->limit(200)
            ->get();

        foreach ($users as $user) {
            Notification::create([
                'userId' => (string) $user->_id,
                'type' => 'OFFRE_SPECIALE',
                'message' => "Offre speciale: profitez de {$activePromo->remise_pourcent}% avec le code {$activePromo->codePromo}.",
                'estLue' => false,
            ]);
        }
    }

    $this->info('Notifications automatiques envoyees.');
})->purpose('Send stay reminders and special offer notifications');

Schedule::command('notifications:send-automated')->hourly();
