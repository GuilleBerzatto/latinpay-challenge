<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\BankReconciliation;
use App\Enums\PaymentStatus; // Asumimos que existe
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Clase ProcessDailyReconciliationJob
 * 
 * Procesa el extracto bancario diario para conciliar los movimientos del banco
 * con los registros internos del Bridge. Implementa lógica de confirmación tardía
 * y detección de discrepancias.
 * 
 * @package App\Jobs
 */
class ProcessDailyReconciliationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array $movements Lista de movimientos extraídos del archivo bancario.
     * @param string $bank Nombre de la entidad bancaria.
     * @param string $processDate Fecha de proceso del extracto.
     */
    public function __construct(
        public array $movements,
        public string $bank,
        public string $processDate
    ) {}

    /**
     * Punto de entrada del Job. Itera sobre los movimientos.
     */
    public function handle(): void
    {
        foreach ($this->movements as $mov) {
            try {
                $this->processMovement($mov);
            } catch (\Exception $e) {
                Log::error("Fallo en movimiento {$mov['bank_movement_id']}: " . $e->getMessage());
            }
        }
    }

    /**
     * Procesa un movimiento individual bajo una transacción.
     * 
     * @param array $mov
     */
    private function processMovement(array $mov): void
    {
        DB::transaction(function () use ($mov) {
            // Idempotencia
            if (BankReconciliation::where('bank_movement_id', $mov['bank_movement_id'])->exists()) {
                return;
            }
            $payment = Payment::where('payment_code', $mov['payment_code'])
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                $this->logReconciliation($mov, 'UNMATCHED', 'El código de pago no existe en el sistema.');
                return;
            }
            if (!$this->validateIntegrity($payment, $mov)) {
                return;
            }
            // Determinación de estado y confirmación tardía
            $note = $this->updatePaymentStatus($payment, $mov);
            // Registro de éxito
            $this->logReconciliation($mov, 'CONCILIATED', $note);
        });
    }

    /**
     * Valida que el monto y moneda coincidan.
     */
    private function validateIntegrity(Payment $payment, array $mov): bool
    {
        $amountsMatch = bccomp((string)$payment->amount, (string)$mov['amount'], 2) === 0;
        $currencyMatches = $payment->currency === $mov['currency'];
        if (!$amountsMatch || !$currencyMatches) {
            $payment->update(['status' => PaymentStatus::OBSERVED]);
            $reason = !$amountsMatch ? "monto incorrecto" : "moneda incorrecta";
            $this->logReconciliation($mov, 'MISMATCH', "Inconsistencia: $reason.");
            return false;
        }
        return true;
    }

    /**
     * Actualiza el pago según su estado previo.
     */
    private function updatePaymentStatus(Payment $payment, array $mov): string
    {
        if ($payment->status === PaymentStatus::PENDING) {
            $payment->update([
                'status' => PaymentStatus::PAID,
                'paid_at' => $mov['paid_at']
            ]);
            return 'Conciliado como confirmación tardía (Módulo D).';
        }

        return "Conciliado (Estado previo: {$payment->status->value}).";
    }

    /**
     * Persiste la trazabilidad en la tabla de conciliación.
     */
    private function logReconciliation(array $mov, string $status, string $notes): void 
    {
        BankReconciliation::create([
            'bank_movement_id'    => $mov['bank_movement_id'],
            'bank_transaction_id' => $mov['bank_transaction_id'],
            'payment_code'        => $mov['payment_code'],
            'amount'              => $mov['amount'],
            'status'              => $status,
            'notes'               => $notes
        ]);
    }
}