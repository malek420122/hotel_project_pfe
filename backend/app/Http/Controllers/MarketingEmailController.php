<?php

namespace App\Http\Controllers;

use App\Mail\SpecialOfferMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MarketingEmailController extends Controller
{
    public function sendSpecialOffer(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:120',
            'message' => 'required|string|max:5000',
            'discountCode' => 'nullable|string|max:50',
        ]);

        $clients = User::where('role', 'client')->where('est_actif', true)->get(['email', 'prenom']);

        foreach ($clients as $client) {
            if (! empty($client->email)) {
                Mail::to($client->email)->queue(new SpecialOfferMail(
                    $request->subject,
                    $request->message,
                    $request->discountCode,
                    $client->prenom ?? 'Client'
                ));
            }
        }

        // Audit log for marketing campaign dispatch in queued mode.
        Log::info('Marketing offer emails queued', [
            'subject' => (string) $request->subject,
            'queued_count' => (int) $clients->count(),
        ]);

        return response()->json([
            'message' => 'Offre speciale envoyee.',
            'sent' => $clients->count(),
        ]);
    }
}
