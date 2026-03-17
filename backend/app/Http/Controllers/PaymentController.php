<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'provider' => 'nullable|string|max:50',
            'transaction_reference' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => __('messages.validation_failed'), 'errors' => $validator->errors()], 422);
        }

        $payment = $this->paymentService->process($validator->validated());

        Booking::where('_id', $payment->booking_id)->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        return response()->json([
            'message' => __('messages.payment_success'),
            'data' => $payment,
        ], 201);
    }
}
