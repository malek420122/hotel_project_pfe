<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('mongodb')) {
            $this->markTestSkipped('MongoDB PHP extension is required for Auth feature tests.');
        }
    }

    public function test_user_can_register()
    {
        $this->withoutMiddleware();
        $this->withoutExceptionHandling();
        
        $email = 'test.' . bin2hex(random_bytes(4)) . '@example.com';
        
        $response = $this->postJson('/api/auth/register', [
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => $email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'telephone' => '12345678',
            'role' => 'client'
        ]);

        $response->assertStatus(201);
    }

    public function test_user_can_login()
    {
        $this->withoutMiddleware();
        $this->withoutExceptionHandling();
        
        $email = 'login.' . bin2hex(random_bytes(4)) . '@example.com';
        
        User::create([
            'nom' => 'Login',
            'prenom' => 'Test',
            'email' => $email,
            'password' => \Illuminate\Support\Facades\Hash::make('Password123!'),
            'role' => 'client'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'Password123!'
        ]);

        $response->assertStatus(200);
    }
}
