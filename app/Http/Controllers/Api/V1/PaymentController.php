<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePaymentRequest;
use App\Services\PaymentService;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller {
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function store(StorePaymentRequest $request): JsonResponse {
        $data = $request->validated();
        
        // Genera código único mediante el Service
        $data['payment_code'] = $this->paymentService->generatePaymentCode();
        
        // El status se asigna automáticamente como 'PENDING' en el modelo
        $payment = Payment::create($data);

        return response()->json([
            'payment_code' => $payment->payment_code,
            'status'       => $payment->status,
            'amount'       => number_format($payment->amount, 2, '.', ''), // Asegura 2 decimales string
            'currency'     => $payment->currency,
        ], 201);
    }
}