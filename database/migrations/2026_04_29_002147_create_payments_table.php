<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique();
            $table->string('status');
            $table->decimal('amount', 16, 4); // Alta precisión
            $table->string('currency', 3);
            $table->unsignedBigInteger('merchant_id');
            $table->string('customer_document');
            $table->text('description')->nullable();
            $table->timestamps(); // Esto cubre la "Fecha de creación" requerida
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
