<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model {
    protected $fillable = [
        'payment_code',
        'merchant_id',
        'customer_document',
        'amount',
        'currency',
        'description',
        'status'
    ];

    // Regla: Estado inicial PENDING por defecto
    protected $attributes = [
        'status' => 'PENDING',
    ];

    /**
     * Filtra los pagos aptos para liquidación según las reglas del Módulo F.
     */
    public function scopeEligibleForSettlement($query)
    {
        // Usamos strings claros para los parámetros
        $timezone = 'America/Lima';
        $cutOff = '20:45:00';

        return $query->whereIn('status', [
                    \App\Enums\PaymentStatus::PAID, 
                    \App\Enums\PaymentStatus::RECONCILED
                ])
                ->whereNull('settled_at')
                ->whereNotNull('paid_at') // Evita errores con nulos
                ->whereRaw(
                    "(paid_at AT TIME ZONE 'UTC' AT TIME ZONE ?)::time <= ?::time", 
                    [$timezone, $cutOff]
                );
    }
}