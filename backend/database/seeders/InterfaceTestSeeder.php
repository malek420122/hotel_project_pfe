<?php

namespace Database\Seeders;

use App\Models\Chambre;
use App\Models\Hotel;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InterfaceTestSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            ['prenom' => 'Sondess', 'nom' => 'Haj'],
            ['prenom' => 'Sarra', 'nom' => 'Sarour'],
            ['prenom' => 'Mariem', 'nom' => 'Ben Ali'],
            ['prenom' => 'Ameni', 'nom' => 'Ghrab'],
            ['prenom' => 'Zaineb', 'nom' => 'Trabelsi'],
            ['prenom' => 'Oumaima', 'nom' => 'Karray'],
        ];
        $hotel = Hotel::first();
        if (!$hotel) return;

        $rooms = Chambre::where('hotelId', (string) $hotel->_id)->get();

        foreach ($clients as $index => $cData) {
            $prenom = $cData['prenom'];
            $nom = $cData['nom'];
            $user = User::updateOrCreate(
                ['email' => strtolower($prenom) . '.' . strtolower($nom) . '@example.com'],
                [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'password' => Hash::make('Password123!'),
                    'role' => 'client',
                    'est_actif' => true,
                    'langue' => 'fr'
                ]
            );

            $room = $rooms->get($index);
            if (!$room) continue;

            $reservation = Reservation::updateOrCreate(
                ['reference' => 'TEST-' . strtoupper($prenom)],
                [
                    'clientId' => (string) $user->_id,
                    'chambreId' => (string) $room->_id,
                    'hotelId' => (string) $hotel->_id,
                    'dateArrivee' => ($index < 3) ? now()->subDays(2)->toDateString() : now()->toDateString(),
                    'dateDepart' => ($index < 3) ? now()->toDateString() : now()->addDays(2)->toDateString(), 
                    'nbVoyageurs' => 1,
                    'statut' => ($index < 3) ? 'EN_COURS' : 'CONFIRMEE',
                    'prixTotal' => 150 + ($index * 10),
                    'demandesSpeciales' => 'Test client ' . $prenom,
                    'checkinAt' => ($index < 3) ? now()->subDays(2) : null,
                ]
            );

            Paiement::updateOrCreate(
                ['reservationId' => (string) $reservation->_id],
                [
                    'montant' => $reservation->prixTotal,
                    'statut' => 'PAYE',
                    'methode' => 'carte'
                ]
            );
            
            if ($index < 3) {
                $room->update(['statut' => 'OCCUPE', 'estDisponible' => false]);
            } else {
                $room->update(['statut' => 'LIBRE', 'estDisponible' => true]);
            }
        }
    }
}
