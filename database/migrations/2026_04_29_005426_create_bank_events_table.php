<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bank_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('bank_transaction_id')->index();
            $table->string('payment_code');
            $table->decimal('amount', 16, 4);
            $table->string('processing_status'); // PENDING, PROCESSED, DUPLICATE, FAILED
            $table->text('notes')->nullable(); // AQUÍ queda la trazabilidad
            $table->jsonb('raw_payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_events');
    }
};
