<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We rely on middleware role check (admin)
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'adresse' => 'required|string|max:500',
            'ville' => 'required|string|max:100',
            'etoiles' => 'required|integer|between:1,5',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'photos' => 'nullable|array',
            'equipements' => 'nullable|array',
        ];
    }
}
