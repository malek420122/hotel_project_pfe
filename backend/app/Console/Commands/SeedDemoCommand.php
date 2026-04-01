<?php

namespace App\Console\Commands;

use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedDemoCommand extends Command
{
    protected $signature = 'app:seed-demo';
    protected $description = 'Seed professional demo data (user, hotel, room, optional reservation)';

    public function handle(): int
    {
        $root = realpath(base_path('..')) ?: base_path('..');

        $userPayload = $this->readJson($root . DIRECTORY_SEPARATOR . 'demo_user.json', false);
        $reservationPayload = $this->readJson($root . DIRECTORY_SEPARATOR . 'demo_reservation.json', false);

        if (! $userPayload) {
            $userPayload = [
                'nom' => 'Demo',
                'prenom' => 'User',
                'email' => 'demo@example.com',
                'password' => 'Password1',
                'telephone' => '+33000000000',
                'langue' => 'en',
            ];
            $this->warn('demo_user.json not found in container, using built-in defaults.');
        }

        if (! $reservationPayload) {
            $reservationPayload = [
                'dateArrivee' => Carbon::now()->addDays(2)->toDateString(),
                'dateDepart' => Carbon::now()->addDays(5)->toDateString(),
                'nbVoyageurs' => 2,
            ];
            $this->warn('demo_reservation.json not found in container, using built-in defaults.');
        }

        $user = User::where('email', $userPayload['email'])->first();
        if (! $user) {
            $user = User::create([
                'nom' => $userPayload['nom'] ?? 'Demo',
                'prenom' => $userPayload['prenom'] ?? 'User',
                'email' => $userPayload['email'],
                'password' => Hash::make($userPayload['password'] ?? 'Password1'),
                'telephone' => $userPayload['telephone'] ?? '+33000000000',
                'langue' => $userPayload['langue'] ?? 'en',
                'role' => 'client',
            ]);
            $this->info('Demo user created: ' . $user->email);
        } else {
            $this->info('Demo user already exists: ' . $user->email);
        }

        $hotel = Hotel::firstOrCreate(
            ['nom' => 'HotelEase Demo Hotel', 'ville' => 'Paris'],
            [
                'description' => 'Professional demo hotel used for PFE presentation',
                'adresse' => '10 Avenue de la Demo, 75008 Paris',
                'latitude' => 48.8717,
                'longitude' => 2.3076,
                'etoiles' => 4,
                'estActif' => true,
                'equipements' => ['wifi', 'spa', 'restaurant'],
                'photos' => [],
            ]
        );

        $chambre = Chambre::firstOrCreate(
            ['hotelId' => (string) $hotel->_id, 'nom' => 'Chambre Classique Demo'],
            [
                'type' => 'DOUBLE',
                'description' => 'Chambre de demonstration pour reservation client',
                'prix_base' => 185,
                'maxVoyageurs' => 2,
                'equipements' => ['wifi', 'tv'],
                'photos' => [],
                'estDisponible' => true,
                'etage' => 1,
            ]
        );

        $this->info('Demo hotel id: ' . (string) $hotel->_id);
        $this->info('Demo room id: ' . (string) $chambre->_id);

        if ($reservationPayload) {
            $dateArrivee = $reservationPayload['dateArrivee'] ?? Carbon::now()->addDays(2)->toDateString();
            $dateDepart = $reservationPayload['dateDepart'] ?? Carbon::now()->addDays(5)->toDateString();
            $nbVoyageurs = (int) ($reservationPayload['nbVoyageurs'] ?? 2);

            $existingReservation = Reservation::where('clientId', (string) $user->_id)
                ->where('hotelId', (string) $hotel->_id)
                ->where('chambreId', (string) $chambre->_id)
                ->where('dateArrivee', $dateArrivee)
                ->where('dateDepart', $dateDepart)
                ->first();

            if (! $existingReservation) {
                $nuits = max(1, Carbon::parse($dateArrivee)->diffInDays(Carbon::parse($dateDepart)));
                $prixTotal = $nuits * ((float) $chambre->prix_base);

                $reservation = Reservation::create([
                    'reference' => 'R-DEMO-' . now()->format('YmdHis'),
                    'clientId' => (string) $user->_id,
                    'hotelId' => (string) $hotel->_id,
                    'chambreId' => (string) $chambre->_id,
                    'dateArrivee' => $dateArrivee,
                    'dateDepart' => $dateDepart,
                    'nbVoyageurs' => $nbVoyageurs,
                    'statut' => 'CONFIRMEE',
                    'prixTotal' => $prixTotal,
                    'servicesChoisis' => [],
                    'demandesSpeciales' => '',
                    'codePromoApplique' => '',
                    'remiseAppliquee' => 0,
                ]);

                Paiement::firstOrCreate(
                    ['reservationId' => (string) $reservation->_id],
                    [
                        'montant' => $prixTotal,
                        'statut' => 'PAYE',
                        'methode' => 'carte',
                        'transactionId' => 'TXN-DEMO-' . now()->format('YmdHis'),
                    ]
                );

                $this->info('Demo reservation created: ' . $reservation->reference);
            } else {
                $this->info('Demo reservation already exists: ' . $existingReservation->reference);
            }
        }

        $this->line('Demo login: demo@example.com / Password1');

        return self::SUCCESS;
    }

    private function readJson(string $path, bool $required = true): ?array
    {
        if (! file_exists($path)) {
            return $required ? null : [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : ($required ? null : []);
    }
}
