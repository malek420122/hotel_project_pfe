<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We rely on middleware role check
    }

    public function rules(): array
    {
        return [
            'chambreId' => 'required|string',
            'hotelId' => 'required|string',
            'dateArrivee' => 'required|date|after_or_equal:today',
            'dateDepart' => 'required|date|after:dateArrivee',
            'nbVoyageurs' => 'required|integer|min:1',
            'codePromo' => 'nullable|string',
            'demandesSpeciales' => 'nullable|string',
            'servicesChoisis' => 'nullable|array',
            'methodePaiement' => 'nullable|string|in:carte,virement,especes',
        ];
    }
}
