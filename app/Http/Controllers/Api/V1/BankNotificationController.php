<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BankEvent;
use App\Jobs\ProcessBankNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BankNotificationController extends Controller
{
    public function store(Request $request): JsonResponse {
        // Validación de Payload
        $data = $request->validate([
            'event_id' => 'required|string',
            'bank_transaction_id' => 'required|string',
            'payment_code' => 'required|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string|size:3',
            'status' => 'required|string',
            'paid_at' => 'required|date',
        ]);

        // IDEMPOTENCIA (event_id): Evita procesar dos veces lo mismo
        if (BankEvent::where('event_id', $data['event_id'])->exists()) {
            return response()->json(['message' => 'Event already registered'], 200);
        }

        // REGISTRO
        $event = BankEvent::create([
            'event_id' => $data['event_id'],
            'payment_code' => $data['payment_code'],
            'amount' => $data['amount'],
            'bank_transaction_id' => $data['bank_transaction_id'],
            'raw_payload' => $request->all(), // Guardamos el original
            'processing_status' => 'PENDING'
        ]);

        // Lógica asíncrona
        ProcessBankNotificationJob::dispatch($event);

        return response()->json(['message' => 'Notification accepted'], 202);
    }
}