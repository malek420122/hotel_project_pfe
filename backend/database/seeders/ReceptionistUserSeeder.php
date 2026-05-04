<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ReceptionistUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'receptionist@hotelease.com',
        ], [
            'nom' => 'Receptionist',
            'prenom' => 'Demo',
            'email' => 'receptionist@hotelease.com',
            'password' => Hash::make('Receptionist123!'),
            'telephone' => '0123456789',
            'role' => 'receptionniste',
            'est_actif' => true,
        ]);
    }
}
