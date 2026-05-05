<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clase SendWebhookNotificationJob
 * 
 * Gestiona la notificación asíncrona hacia los comercios externos.
 * Implementa políticas de reintento ante fallos de red y garantiza
 * que el comercio sea informado sobre el cambio de estado de sus pagos.
 * 
 * @package App\Jobs
 */
class SendWebhookNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos antes de marcar el Job como fallido.
     */
    public $tries = 3;

    /**
     * Segundos de espera entre cada reintento (Exponencial o Fijo).
     */
    public $backoff = 60;

    /**
     * Crea una nueva instancia del Job.
     * 
     * @param Payment $payment Registro del pago a notificar.
     */
    public function __construct(public Payment $payment) {}

    /**
     * Ejecuta la petición HTTP hacia el endpoint del comercio.
     */
    public function handle(): void
    {
        Log::info("Enviando notificación de pago: [{$this->payment->payment_code}]");
        $webhookUrl = 'https://webhook.site/93694232-5f05-4919-b679-606bcff3988d';
        try {
            $response = Http::withHeaders([
                'X-Source' => 'Payment-Bridge',
                'User-Agent' => 'Bridge-Notifier/1.0'
            ])
            ->timeout(5)
            ->post($webhookUrl, $this->formatPayload());
            if ($response->failed()) {
                $this->handleFailure($response->status());
            } else {
                Log::info("Notificación exitosa para pago: [{$this->payment->payment_code}]");
            }

        } catch (\Exception $e) {
            Log::error("Error de conexión al notificar Webhook: " . $e->getMessage());
            throw $e; // Permite que el sistema de colas use el $backoff
        }
    }

    /**
     * Estructura el cuerpo de la petición.
     * 
     * @return array
     */
    private function formatPayload(): array
    {
        return [
            'payment_code' => $this->payment->payment_code,
            'status'       => $this->payment->status,
            'amount'       => $this->payment->amount,
            'currency'     => $this->payment->currency,
            'confirmed_at' => $this->payment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * Gestiona la lógica en caso de respuesta no exitosa (4xx, 5xx).
     */
    private function handleFailure(int $statusCode): void
    {
        Log::warning("Comercio rechazó notificación [{$this->payment->payment_code}] con status: {$statusCode}");
        if ($statusCode >= 500) {
            $this->release($this->backoff);
        }
    }
}