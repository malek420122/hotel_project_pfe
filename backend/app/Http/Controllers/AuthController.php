<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        // Set default values for optional fields if not provided
        $validated['nationalite'] = $validated['nationalite'] ?? '';
        $validated['langue'] = $validated['langue'] ?? 'fr';

        $user = User::create($validated);

        // Envoyer une notification de bienvenue
        try {
            Notification::create([
                'userId' => (string) $user->_id,
                'type' => 'BIENVENUE',
                'message' => "Bienvenue sur HotelEase, {$user->prenom} !",
                'estLue' => false,
            ]);
        } catch (\Exception $e) {
            // Logger l'erreur mais ne pas bloquer l'inscription
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        if ($user->bloque_jusqu_a && Carbon::parse($user->bloque_jusqu_a)->isFuture()) {
            $minutesLeft = Carbon::now()->diffInMinutes(Carbon::parse($user->bloque_jusqu_a));
            return response()->json([
                'error' => "Compte temporairement bloqué. Réessayez dans {$minutesLeft} minutes."
            ], 423);
        }

        if (!Hash::check($request->password, $user->password)) {
            $tentatives = ($user->tentatives_connexion ?? 0) + 1;
            $updates = ['tentatives_connexion' => $tentatives];
            if ($tentatives >= 5) {
                $updates['bloque_jusqu_a'] = Carbon::now()->addMinutes(30)->toDateTimeString();
            }
            $user->update($updates);
            return response()->json(['error' => 'Identifiants invalides'], 401);
        }

        $user->update(['tentatives_connexion' => 0, 'bloque_jusqu_a' => null]);

        $token = JWTAuth::fromUser($user);
        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function me()
    {
        $user = JWTAuth::parseToken()->authenticate();
        return response()->json($user);
    }
}
