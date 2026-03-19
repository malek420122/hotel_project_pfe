<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hotel;
use App\Models\Chambre;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $hotel = Hotel::create([
            'nom' => 'Demo Hotel',
            'description' => 'Hôtel de démonstration pour tests',
            'adresse' => '1 Rue de Test',
            'ville' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'etoiles' => 4,
            'photos' => [],
            'noteMoyenne' => 4.5,
            'equipements' => ['wifi', 'parking'],
            'estActif' => true,
        ]);

        Chambre::create([
            'hotelId' => (string) $hotel->_id,
            'type' => 'DOUBLE',
            'nom' => 'Chambre Démo 101',
            'description' => 'Chambre double confortable',
            'prix_base' => 80.00,
            'maxVoyageurs' => 2,
            'equipements' => ['climatisation', 'tv'],
            'photos' => [],
            'estDisponible' => true,
            'etage' => 1,
        ]);
    }
}
