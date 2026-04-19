<?php

namespace Database\Seeders;

use App\Models\Avis;
use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Notification;
use App\Models\Paiement;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RealisticDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $clients = collect([
            ['prenom' => 'Demo', 'nom' => 'User', 'email' => 'demo.user@example.com', 'points' => 8420, 'niveau' => 'Or', 'nationalite' => 'France'],
            ['prenom' => 'Ahmed', 'nom' => 'Benali', 'email' => 'ahmed.benali@example.com', 'points' => 6180, 'niveau' => 'Or', 'nationalite' => 'Maroc'],
            ['prenom' => 'Laura', 'nom' => 'Dupont', 'email' => 'laura.dupont@example.com', 'points' => 4920, 'niveau' => 'Argent', 'nationalite' => 'France'],
            ['prenom' => 'Maria', 'nom' => 'Santos', 'email' => 'maria.santos@example.com', 'points' => 2960, 'niveau' => 'Argent', 'nationalite' => 'Espagne'],
        ])->map(function (array $data) {
            return User::updateOrCreate([
                'email' => $data['email'],
            ], [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'password' => Hash::make('Client123!'),
                'telephone' => '0600000000',
                'nationalite' => $data['nationalite'],
                'langue' => 'fr',
                'role' => 'client',
                'points_fidelite' => $data['points'],
                'niveau_fidelite' => $data['niveau'],
                'est_actif' => true,
            ]);
        });

        User::updateOrCreate([
            'email' => 'reception@example.com',
        ], [
            'nom' => 'Reception',
            'prenom' => 'Team',
            'email' => 'reception@example.com',
            'password' => Hash::make('Reception123!'),
            'telephone' => '0600000001',
            'role' => 'receptionniste',
            'est_actif' => true,
        ]);

        User::updateOrCreate([
            'email' => 'marketing@example.com',
        ], [
            'nom' => 'Marketing',
            'prenom' => 'Team',
            'email' => 'marketing@example.com',
            'password' => Hash::make('Marketing123!'),
            'telephone' => '0600000002',
            'role' => 'marketing',
            'est_actif' => true,
        ]);

        $hotels = Hotel::orderBy('created_at')->get();
        $rooms = Chambre::orderBy('created_at')->get();

        $services = collect([
            ['hotel' => $hotels->first(), 'categorie' => 'SPA', 'nom' => 'Spa Signature', 'description' => 'Acces complet au spa et wellness', 'prix' => 35, 'capacite' => 6],
            ['hotel' => $hotels->first(), 'categorie' => 'RESTAURANT', 'nom' => 'Petit-dejeuner Premium', 'description' => 'Buffet premium pour deux personnes', 'prix' => 28, 'capacite' => 20],
            ['hotel' => $hotels->skip(1)->first(), 'categorie' => 'ACTIVITE', 'nom' => 'Navette aeroport', 'description' => 'Transfert prive aller-retour', 'prix' => 45, 'capacite' => 4],
            ['hotel' => $hotels->skip(2)->first(), 'categorie' => 'CONCIERGERIE', 'nom' => 'Concierge VIP', 'description' => 'Organisation sur mesure', 'prix' => 60, 'capacite' => 2],
        ])->filter(fn ($service) => $service['hotel'] instanceof Hotel)->map(function (array $service) {
            return Service::updateOrCreate([
                'hotelId' => (string) $service['hotel']->_id,
                'nom' => $service['nom'],
            ], [
                'hotelId' => (string) $service['hotel']->_id,
                'categorie' => $service['categorie'],
                'nom' => $service['nom'],
                'description' => $service['description'],
                'prix' => $service['prix'],
                'capacite' => $service['capacite'],
                'creneaux' => [],
                'estActif' => true,
            ]);
        });

        collect([
            ['titre' => 'Offer Summer Bliss', 'description' => '10% de remise sur les sejours d ete', 'code' => 'SUMMER25', 'remise' => 10, 'active' => true, 'uses' => 89, 'limit' => 200],
            ['titre' => 'Ramadan Special', 'description' => '15% de remise pour le mois sacre', 'code' => 'RAMADAN15', 'remise' => 15, 'active' => true, 'uses' => 45, 'limit' => 150],
            ['titre' => 'Winter Escape', 'description' => '20% sur les sejours en basse saison', 'code' => 'WINTER20', 'remise' => 20, 'active' => false, 'uses' => 120, 'limit' => 120],
            ['titre' => 'Welcome Back', 'description' => '8% de remise pour les clients fideles', 'code' => 'WELCOME8', 'remise' => 8, 'active' => true, 'uses' => 54, 'limit' => 250],
        ])->map(function (array $promo) use ($rooms) {
            return Promotion::updateOrCreate([
                'codePromo' => $promo['code'],
            ], [
                'titre' => $promo['titre'],
                'description' => $promo['description'],
                'remise_pourcent' => $promo['remise'],
                'codePromo' => $promo['code'],
                'dateDebut' => now()->subDays(20)->toDateString(),
                'dateFin' => now()->addDays(60)->toDateString(),
                'chambresIds' => $rooms->take(3)->pluck('_id')->map(fn ($id) => (string) $id)->all(),
                'estActive' => $promo['active'],
                'nbUtilisations' => $promo['uses'],
                'limiteUtilisations' => $promo['limit'],
            ]);
        });

        $reservationFixtures = [
            ['reference' => 'R-2026-1001', 'status' => 'TERMINEE', 'daysAgo' => 21, 'nights' => 3, 'clientIndex' => 0, 'hotelIndex' => 0, 'roomIndex' => 0, 'price' => 3300, 'special' => 'Vue mer et late checkout', 'promo' => 'SUMMER25', 'method' => 'carte'],
            ['reference' => 'R-2026-1002', 'status' => 'CONFIRMEE', 'daysAgo' => 9, 'nights' => 2, 'clientIndex' => 1, 'hotelIndex' => 1, 'roomIndex' => 0, 'price' => 2200, 'special' => 'Transfer aeroport urgent', 'promo' => 'RAMADAN15', 'method' => 'carte'],
            ['reference' => 'R-2026-1003', 'status' => 'EN_COURS', 'daysAgo' => 2, 'nights' => 4, 'clientIndex' => 2, 'hotelIndex' => 2, 'roomIndex' => 0, 'price' => 4100, 'special' => 'Mini bar premium', 'promo' => 'WELCOME8', 'method' => 'virement'],
            ['reference' => 'R-2026-1004', 'status' => 'EN_ATTENTE', 'daysAgo' => 1, 'nights' => 1, 'clientIndex' => 3, 'hotelIndex' => 3, 'roomIndex' => 0, 'price' => 980, 'special' => 'Arrivee tardive', 'promo' => null, 'method' => 'carte'],
            ['reference' => 'R-2026-1005', 'status' => 'ANNULEE', 'daysAgo' => 4, 'nights' => 2, 'clientIndex' => 0, 'hotelIndex' => 4, 'roomIndex' => 0, 'price' => 1500, 'special' => 'Chambre calme', 'promo' => null, 'method' => 'carte'],
            ['reference' => 'R-2026-1006', 'status' => 'TERMINEE', 'daysAgo' => 35, 'nights' => 5, 'clientIndex' => 1, 'hotelIndex' => 5, 'roomIndex' => 0, 'price' => 5200, 'special' => 'Piscine privee', 'promo' => 'WELCOME8', 'method' => 'carte'],
        ];

        foreach ($reservationFixtures as $fixture) {
            $hotel = $hotels->get($fixture['hotelIndex'] % max(1, $hotels->count()));
            $roomPool = $rooms->where('hotelId', (string) $hotel?->_id)->values();
            $room = $roomPool->get($fixture['roomIndex'] % max(1, $roomPool->count())) ?? $rooms->get($fixture['roomIndex'] % max(1, $rooms->count()));
            $client = $clients->get($fixture['clientIndex'] % max(1, $clients->count()));

            if (! $hotel || ! $room || ! $client) {
                continue;
            }

            $dateArrivee = now()->subDays($fixture['daysAgo'])->startOfDay();
            $dateDepart = (clone $dateArrivee)->addDays($fixture['nights']);

            $reservation = Reservation::updateOrCreate([
                'reference' => $fixture['reference'],
            ], [
                'reference' => $fixture['reference'],
                'clientId' => (string) $client->_id,
                'chambreId' => (string) $room->_id,
                'hotelId' => (string) $hotel->_id,
                'dateArrivee' => $dateArrivee->toDateString(),
                'dateDepart' => $dateDepart->toDateString(),
                'nbVoyageurs' => 2,
                'statut' => $fixture['status'],
                'prixTotal' => $fixture['price'],
                'demandesSpeciales' => $fixture['special'],
                'servicesChoisis' => $services->take(2)->map(fn (Service $service) => [
                    'id' => (string) $service->_id,
                    'nom' => $service->nom,
                    'prix' => $service->prix,
                ])->all(),
                'codePromoApplique' => $fixture['promo'] ?? '',
                'remiseAppliquee' => $fixture['promo'] ? 10 : 0,
                'checkinAt' => in_array($fixture['status'], ['EN_COURS', 'TERMINEE']) ? now()->subDays(3) : null,
                'checkoutAt' => $fixture['status'] === 'TERMINEE' ? now()->subDays(1) : null,
            ]);

            Paiement::updateOrCreate([
                'reservationId' => (string) $reservation->_id,
            ], [
                'reservationId' => (string) $reservation->_id,
                'montant' => $reservation->prixTotal,
                'statut' => $fixture['status'] === 'ANNULEE' ? 'ANNULE' : ($fixture['status'] === 'EN_ATTENTE' ? 'EN_COURS' : 'PAYE'),
                'methode' => $fixture['method'],
                'transactionId' => $fixture['status'] === 'ANNULEE' ? null : 'TXN-' . Str::upper(Str::random(10)),
            ]);

            Notification::updateOrCreate([
                'userId' => (string) $client->_id,
                'type' => 'RESERVATION_' . Str::upper($fixture['status']),
                'message' => "Votre reservation {$reservation->reference} est {$fixture['status']}.",
            ], [
                'userId' => (string) $client->_id,
                'type' => 'RESERVATION_' . Str::upper($fixture['status']),
                'message' => "Votre reservation {$reservation->reference} est {$fixture['status']}.",
                'estLue' => false,
            ]);

            if (in_array($fixture['status'], ['TERMINEE', 'EN_COURS'], true)) {
                Avis::updateOrCreate([
                    'reservationId' => (string) $reservation->_id,
                ], [
                    'clientId' => (string) $client->_id,
                    'hotelId' => (string) $hotel->_id,
                    'reservationId' => (string) $reservation->_id,
                    'note' => $fixture['status'] === 'TERMINEE' ? 9 : 8,
                    'commentaire' => $fixture['status'] === 'TERMINEE' ? 'Excellent sejour, service irreprochable.' : 'Bon debut de sejour, equipe reactive.',
                    'statut' => 'PUBLIE',
                    'reponseHotel' => '',
                ]);
            }
        }

        foreach ($hotels as $index => $hotel) {
            Chambre::where('hotelId', (string) $hotel->_id)->take(1)->update(['statut' => 'OCCUPE', 'estDisponible' => false]);
            Chambre::where('hotelId', (string) $hotel->_id)->skip(1)->take(1)->update(['statut' => 'NETTOYAGE', 'estDisponible' => false]);
            if ($index % 3 === 0) {
                Chambre::where('hotelId', (string) $hotel->_id)->skip(2)->take(1)->update(['statut' => 'ENTRETIEN', 'estDisponible' => false]);
            }
        }
    }
}