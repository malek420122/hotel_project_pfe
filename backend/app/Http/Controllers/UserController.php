<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get());
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->only(['nom', 'prenom', 'telephone', 'nationalite', 'langue', 'role', 'est_actif']);
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return response()->json($user);
    }

    public function updateProfile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $data = $request->only(['nom', 'prenom', 'telephone', 'nationalite', 'langue']);
        if ($request->has('password')) {
            $request->validate(['password' => 'min:8|confirmed|regex:/^(?=.*[A-Z])(?=.*\d).+$/']);
            $data['password'] = Hash::make($request->password);
        }
        $user->update($data);
        return response()->json($user);
    }

    public function reservations($id)
    {
        return response()->json(Reservation::where('clientId', $id)->orderBy('created_at', 'desc')->get());
    }

    public function fidelite()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $reservations = Reservation::where('clientId', (string) $user->_id)->where('statut', 'TERMINEE')->get();
        $historique = $reservations->map(function ($r) {
            return [
                'reference' => $r->reference,
                'date' => $r->checkoutAt ?? $r->created_at,
                'points' => (int) round($r->prixTotal / 10),
                'montant' => $r->prixTotal,
            ];
        });

        return response()->json([
            'points' => $user->points_fidelite,
            'niveau' => $user->niveau_fidelite,
            'historique' => $historique,
            'prochainNiveau' => $user->niveau_fidelite === 'Bronze' ? ['nom' => 'Argent', 'pointsRequis' => 1000] :
                ($user->niveau_fidelite === 'Argent' ? ['nom' => 'Or', 'pointsRequis' => 5000] : null),
        ]);
    }
}
