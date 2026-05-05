<?php

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;

class PaymentService
{
    public function generatePaymentCode(): string
    {
        $date = Carbon::now()->format('Ymd');
        $prefix = "LTP-{$date}-";
        
        // Buscamos el último código generado hoy para incrementar el correlativo
        $lastPayment = Payment::where('payment_code', 'like', "{$prefix}%")
            ->orderBy('payment_code', 'desc')
            ->first();

        $sequence = 1;
        if ($lastPayment) {
            $lastSequence = (int) substr($lastPayment->payment_code, -6);
            $sequence = $lastSequence + 1;
        }

        return $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}