<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $notifs = Notification::where('userId', (string) $user->_id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
        return response()->json($notifs);
    }

    public function markRead($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->update(['estLue' => true]);
        return response()->json($notif);
    }

    public function markAllRead()
    {
        $user = JWTAuth::parseToken()->authenticate();
        Notification::where('userId', (string) $user->_id)->update(['estLue' => true]);
        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    }

    public function sendToAll(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:10',
            'type' => 'nullable|string',
        ]);

        $users = \App\Models\User::where('role', 'client')->get();
        foreach ($users as $user) {
            Notification::create([
                'userId' => (string) $user->_id,
                'type' => $request->type ?? 'MARKETING',
                'message' => $request->message,
                'estLue' => false,
            ]);
        }

        return response()->json(['message' => "Notification envoyée à " . count($users) . " clients."]);
    }
}
