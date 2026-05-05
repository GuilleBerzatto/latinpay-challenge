<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\BankEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase; // Reinicia la DB en cada test para limpieza total

    /** @test */
    public function it_can_create_a_payment_intent_module_a()
    {
        $payload = [
            'merchant_id' => 10,
            'customer_document' => '76359665',
            'amount' => 100.00,
            'currency' => 'PEN',
            'description' => 'Test Payment'
        ];

        $response = $this->postJson('/api/v1/payments', $payload);

        $response->assertStatus(201)
                 ->assertJsonStructure(['status', 'data' => ['payment_code']]);
        
        $this->assertDatabaseHas('payments', ['amount' => 100.00]);
    }

    /** @test */
    public function it_enforces_idempotency_in_bank_notifications_module_b()
    {
        // 1. Crear el pago previo
        $payment = Payment::factory()->create(['payment_code' => 'LTP-123', 'status' => 'PENDING']);

        $payload = [
            'event_id' => 'evt_unique_001',
            'bank_transaction_id' => 'tx_999',
            'payment_code' => 'LTP-123',
            'amount' => $payment->amount,
            'currency' => 'PEN',
            'status' => 'PAID',
            'paid_at' => now()->toDateTimeString()
        ];

        // 2. Primera notificación: Debe ser exitosa
        $this->postJson('/api/v1/bank/notifications', $payload)->assertStatus(200);

        // 3. Segunda notificación con mismo event_id: Debe fallar o indicar duplicado
        $response = $this->postJson('/api/v1/bank/notifications', $payload);
        
        $response->assertStatus(422) // O 200 según tu lógica de "already registered"
                 ->assertJsonFragment(['message' => 'event already registered']);
    }

    /** @test */
    public function it_filters_settlement_candidates_by_cutoff_time_module_f()
    {
        // Pago ANTES del corte (20:30) - Debe aparecer
        Payment::factory()->create([
            'status' => 'PAID',
            'paid_at' => '2026-05-05 20:30:00',
            'settled_at' => null
        ]);

        // Pago DESPUÉS del corte (21:00) - No debe aparecer
        Payment::factory()->create([
            'status' => 'PAID',
            'paid_at' => '2026-05-05 21:00:00',
            'settled_at' => null
        ]);

        $response = $this->getJson('/api/v1/settlements/candidates');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data'); // Solo el de las 20:30
    }
}