<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reservation;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:mongodb.users,email',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Z])(?=.*\d).+$/',
            'telephone' => 'nullable|string|max:30',
            'nationalite' => 'nullable|string|max:100',
            'langue' => 'nullable|in:fr,en,ar',
            'role' => 'nullable|in:client,receptionniste,admin,marketing',
            'est_actif' => 'nullable|boolean',
        ]);

        $user = User::create([
            'nom' => $validated['nom'],
            'prenom' => $validated['prenom'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'telephone' => $validated['telephone'] ?? '',
            'nationalite' => $validated['nationalite'] ?? '',
            'langue' => $validated['langue'] ?? 'fr',
            'role' => $validated['role'] ?? 'client',
            'est_actif' => $request->has('est_actif') ? (bool) $validated['est_actif'] : true,
        ]);

        return response()->json($user, 201);
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

    public function destroy($id)
    {
        $currentUser = JWTAuth::parseToken()->authenticate();

        if ((string) $currentUser->_id === (string) $id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    public function updateProfile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $data = $request->only(['nom', 'prenom', 'telephone', 'nationalite', 'langue']);
        if ($request->has('password')) {
            $request->validate(['old_password' => 'required|string']);
            if (!Hash::check((string) $request->old_password, (string) $user->password)) {
                return response()->json(['message' => 'Ancien mot de passe incorrect'], 422);
            }
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
            'niveaux' => [
                ['name' => 'Bronze', 'icon' => '🥉', 'minPts' => 0, 'maxPts' => 1999, 'benefits' => ['5% remise', 'Check-in prioritaire']],
                ['name' => 'Argent', 'icon' => '🥈', 'minPts' => 2000, 'maxPts' => 4999, 'benefits' => ['10% remise', 'Surclassement gratuit', 'Petit-dej offert']],
                ['name' => 'Or', 'icon' => '🥇', 'minPts' => 5000, 'maxPts' => null, 'benefits' => ['20% remise', 'Surclassement automatique', 'Accès VIP lounge', 'Late checkout']],
            ],
            'historique' => $historique,
            'prochainNiveau' => $user->niveau_fidelite === 'Bronze' ? ['nom' => 'Argent', 'pointsRequis' => 1000] :
                ($user->niveau_fidelite === 'Argent' ? ['nom' => 'Or', 'pointsRequis' => 5000] : null),
        ]);
    }

    public function redeemPoints(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $request->validate([
            'reward' => 'required|in:discount10,free_breakfast,free_upgrade',
        ]);

        $rewardMap = [
            'discount10' => ['cost' => 500, 'title' => 'Récompense 10% fidélité', 'discount' => 10],
            'free_breakfast' => ['cost' => 1000, 'title' => 'Petit-déjeuner offert', 'discount' => 15],
            'free_upgrade' => ['cost' => 2000, 'title' => 'Surclassement offert', 'discount' => 20],
        ];

        $reward = $rewardMap[$request->reward];
        $currentPoints = (int) ($user->points_fidelite ?? 0);

        if ($currentPoints < $reward['cost']) {
            return response()->json(['error' => 'Points insuffisants'], 422);
        }

        $newPoints = $currentPoints - $reward['cost'];
        $niveau = $newPoints >= 5000 ? 'Or' : ($newPoints >= 1000 ? 'Argent' : 'Bronze');

        $code = 'LOYAL-' . strtoupper(substr($request->reward, 0, 3)) . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));

        $promo = Promotion::create([
            'titre' => $reward['title'],
            'description' => 'Code généré via le programme fidélité client',
            'remise_pourcent' => $reward['discount'],
            'codePromo' => $code,
            'dateDebut' => now(),
            'dateFin' => now()->addMonths(3),
            'chambresIds' => [],
            'estActive' => true,
            'nbUtilisations' => 0,
            'limiteUtilisations' => 1,
        ]);

        $user->update([
            'points_fidelite' => $newPoints,
            'niveau_fidelite' => $niveau,
        ]);

        return response()->json([
            'message' => 'Récompense activée avec succès',
            'codePromo' => $promo->codePromo,
            'pointsRestants' => $newPoints,
        ]);
    }

    public function resetPreferences(Request $request, \App\Services\RecommendationService $recommendationService)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $userId = (string) ($user->_id ?? $user->id);

        $recommendationService->resetUserActivities($userId);

        return response()->json([
            'status' => 'success',
            'message' => 'Votre historique et vos recommandations ont été réinitialisés avec succès.'
        ]);
    }
}
