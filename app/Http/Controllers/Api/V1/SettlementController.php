<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;

/**
 * Gestiona la identificación de pagos listos para ser liquidados al comercio.
 */
class SettlementController extends Controller
{
    public function getCandidates(): JsonResponse
    {
        // Usamos el scope que definimos en el modelo
        $candidates = Payment::eligibleForSettlement()->get();

        return response()->json([
            'status' => 'success',
            'meta' => [
                'count' => $candidates->count(),
                'server_time' => now()->toDateTimeString(),
                'timezone' => 'America/Lima'
            ],
            'data' => $candidates
        ]);
    }
}