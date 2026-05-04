<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $notifs = Notification::where('userId', (string) ($user->_id ?? $user->id ?? $user->getKey()))
            ->orderBy('createdAt', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json($notifs->map(fn (Notification $notification) => $this->presentNotification($notification))->values());
    }

    public function markRead($id)
    {
        $notif = Notification::findOrFail($id);
        $notif->update(['vu' => true, 'estLue' => true]);

        return response()->json($this->presentNotification($notif->fresh()));
    }

    public function markAllRead()
    {
        $user = JWTAuth::parseToken()->authenticate();
        Notification::where('userId', (string) ($user->_id ?? $user->id ?? $user->getKey()))
            ->update(['vu' => true, 'estLue' => true]);

        return response()->json(['message' => 'Toutes les notifications marquées comme lues']);
    }

    private function presentNotification(Notification $notification): array
    {
        $vu = (bool) ($notification->vu ?? $notification->estLue ?? false);

        return [
            '_id' => (string) ($notification->_id ?? ''),
            'userId' => (string) ($notification->userId ?? ''),
            'message' => (string) ($notification->message ?? ''),
            'type' => (string) ($notification->type ?? 'INFO'),
            'vu' => $vu,
            'estLue' => $vu,
            'createdAt' => $notification->createdAt ?? $notification->created_at,
            'created_at' => $notification->created_at ?? $notification->createdAt,
        ];
    }
}
