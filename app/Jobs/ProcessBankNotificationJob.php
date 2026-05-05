<?php

namespace App\Jobs;

use App\Models\BankEvent;
use App\Models\Payment;
use App\Enums\PaymentStatus; // Importamos el Enum
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Clase ProcessBankNotificationJob
 * 
 * Se encarga de procesar de forma asíncrona las notificaciones recibidas del banco.
 * Realiza la validación de integridad (monto/moneda) y actualiza el estado del pago.
 * 
 * @package App\Jobs
 */
class ProcessBankNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de reintentos en caso de fallo técnico.
     */
    public $tries = 3;

    /**
     * Crea una nueva instancia del Job.
     * 
     * @param BankEvent $bankEvent Registro del evento crudo recibido.
     */
    public function __construct(public BankEvent $bankEvent) {}

    /**
     * Ejecuta la lógica de procesamiento.
     */
    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // Bloqueo de fila para evitar actualizaciones concurrentes
                $payment = Payment::where('payment_code', $this->bankEvent->payment_code)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    $this->markEventAsFailed('Código de pago no existe en el sistema.');
                    return;
                }

                // Verificación de Idempotencia usando Enums
                if ($payment->status === PaymentStatus::PAID) {
                    $this->bankEvent->update([
                        'processing_status' => 'SKIPPED',
                        'notes' => 'Notificación duplicada: el pago ya estaba confirmado.'
                    ]);
                    return;
                }
                $this->validateAndProcess($payment);
            });
        } catch (\Exception $e) {
            Log::error("Fallo en procesamiento de notificación [{$this->bankEvent->id}]: " . $e->getMessage());
            $this->bankEvent->update([
                'processing_status' => 'FAILED',
                'notes' => 'Excepción técnica: ' . $e->getMessage()
            ]);
            $this->release(30); // Reintento exponencial
        }
    }

    /**
     * Valida montos y actualiza el estado del pago.
     */
    private function validateAndProcess(Payment $payment): void
    {
        $amountsMatch = bccomp((string)$payment->amount, (string)$this->bankEvent->amount, 2) === 0;
        $currencyMatches = $payment->currency === $this->bankEvent->currency;

        if ($amountsMatch && $currencyMatches) {
            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => $this->bankEvent->raw_payload['paid_at'] ?? now()
            ]);
            
            $this->bankEvent->update(['processing_status' => 'PROCESSED', 'notes' => 'Pago validado exitosamente.']);
        } else {
            $payment->update(['status' => PaymentStatus::OBSERVED]);
            
            $reason = !$amountsMatch ? "discrepancia de monto" : "discrepancia de moneda";
            $this->bankEvent->update([
                'processing_status' => 'PROCESSED',
                'notes' => "Estado OBSERVED por $reason. Esperado: $payment->amount $payment->currency"
            ]);
        }
    }

    private function markEventAsFailed(string $message): void
    {
        $this->bankEvent->update([
            'processing_status' => 'FAILED',
            'notes' => $message
        ]);
    }
}