<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankEvent extends Model
{
    protected $fillable = [
        'event_id',
        'payment_code',
        'amount',
        'bank_transaction_id',
        'raw_payload',
        'processing_status'
    ];

    // Importante para PostgreSQL y auditoría
    protected $casts = [
        'raw_payload' => 'array'
    ];
}