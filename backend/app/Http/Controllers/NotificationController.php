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
}
