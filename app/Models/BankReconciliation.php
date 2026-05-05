<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_movement_id',
        'bank_transaction_id',
        'payment_code',
        'amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}