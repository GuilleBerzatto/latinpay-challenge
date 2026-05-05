<?php

namespace App\Observers;

use App\Models\Payment;
use App\Jobs\SendWebhookNotificationJob;

class PaymentObserver
{
    public function updated(Payment $payment): void
    {
        // Solo disparamos si el estado CAMBIÓ a 'PAID' en esta ejecución
        if ($payment->isDirty('status') && $payment->status === 'PAID') {
            SendWebhookNotificationJob::dispatch($payment);
        }
    }
}

