<?php

namespace App\Console\Commands;

use App\Mail\StayReminderMail;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendStayRemindersCommand extends Command
{
    protected $signature = 'app:send-stay-reminders';
    protected $description = 'Send automatic stay reminder emails one day before check-in';

    public function handle(): int
    {
        $targetDate = Carbon::tomorrow()->toDateString();

        $reservations = Reservation::whereDate('dateArrivee', $targetDate)
            ->whereIn('statut', ['EN_ATTENTE', 'CONFIRMEE'])
            ->get();

        $sent = 0;

        foreach ($reservations as $reservation) {
            $client = User::find($reservation->clientId);
            if (! $client || empty($client->email)) {
                continue;
            }

            Mail::to($client->email)->queue(new StayReminderMail($reservation, $client->prenom ?? 'Client'));
            $sent++;
        }

        $this->info("Stay reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
