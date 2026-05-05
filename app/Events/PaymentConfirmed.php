<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Clase PaymentConfirmed
 * 
 * Este evento se dispara cuando un pago ha sido validado satisfactoriamente
 * por el banco (Módulo B) o reconciliado (Módulo D). Sirve como punto de 
 * entrada para múltiples acciones asíncronas.
 * 
 * @package App\Events
 */
class PaymentConfirmed
{
    use Dispatchable, SerializesModels;

    /**
     * Instancia del pago confirmado.
     * 
     * @var Payment
     */
    public Payment $payment;

    /**
     * Crea una nueva instancia del evento.
     * 
     * @param Payment $payment
     * @return void
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }
}