<?php

use App\Http\Controllers\Api\V1\BankNotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReconciliationController;
use App\Http\Controllers\Api\V1\SettlementController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    /**
     * Módulo A & C: Gestión de Pagos
     */
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{code}', [PaymentController::class, 'show']);
    
    /**
     * Módulo B: Notificación del banco (Tiempo Real)
     */
    Route::post('/bank/notifications', [BankNotificationController::class, 'store']);
    
    /**
     * Módulo D: Conciliación Bancaria (Cierre del día)
     */
    Route::post('/bank/reconciliation', [ReconciliationController::class, 'store']);

    /**
     * Módulo F: Liquidación (Candidatos)
     */
    Route::get('/settlements/candidates', [SettlementController::class, 'getCandidates']);

});