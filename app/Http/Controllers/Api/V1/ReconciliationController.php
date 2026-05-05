<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDailyReconciliationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ReconciliationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // Validar la estructura del payload (Módulo D)
        $validator = Validator::make($request->all(), [
            'bank' => 'required|string',
            'process_date' => 'required|date_format:Y-m-d',
            'movements' => 'required|array|min:1',
            'movements.*.bank_movement_id' => 'required|string',
            'movements.*.bank_transaction_id' => 'required|string',
            'movements.*.payment_code' => 'required|string',
            'movements.*.amount' => 'required|numeric',
            'movements.*.currency' => 'required|string|size:3',
            'movements.*.paid_at' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid reconciliation payload',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        // Despachar el Job para procesamiento asíncrono
        ProcessDailyReconciliationJob::dispatch(
            $validated['movements'],
            $validated['bank'],
            $validated['process_date']
        );

        return response()->json([
            'message' => 'Reconciliation process started',
            'process_date' => $validated['process_date'],
            'total_movements' => count($validated['movements'])
        ], 202);
    }
}