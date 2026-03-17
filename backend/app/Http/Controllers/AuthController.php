<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|min:8',
            'phone' => 'nullable|string|max:20',
            'preferred_locale' => 'nullable|string|in:fr,en,ar',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'phone' => $request->input('phone'),
            'preferred_locale' => $request->input('preferred_locale', config('languages.default', 'fr')),
            'role' => 'client',
        ]);

        return response()->json([
            'message' => __('messages.registration_success'),
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => __('messages.invalid_credentials')], 401);
        }

        return response()->json([
            'message' => __('messages.login_success'),
            'user' => $user,
        ]);
    }
}
