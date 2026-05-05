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
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('bank_movement_id')->unique(); // Regla: No procesar duplicados
            $table->string('bank_transaction_id');
            $table->string('payment_code');
            $table->decimal('amount', 16, 4);
            $table->string('status'); // CONCILIATED, MISMATCH, UNMATCHED
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliations');
    }
};
