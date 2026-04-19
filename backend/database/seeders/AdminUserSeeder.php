<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate([
            'email' => 'admin@hotelease.com',
        ], [
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@hotelease.com',
            'password' => Hash::make('Admin123!'),
            'telephone' => '0123456789',
            'role' => 'admin',
            'est_actif' => true,
        ]);
    }
}
