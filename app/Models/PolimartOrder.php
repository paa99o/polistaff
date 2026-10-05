<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class PolimartOrder extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'address_line_1', 'address_line_2', 'city', 'postcode', 'state',
        'note', 'items', 'subtotal', 'shipping_fee', 'total', 'status',
        'payment_method', 'payment_instructions', 'payment_status', 'payment_proof_path', 'payment_reference', 'payment_review_note', 'payment_paid_at', 'payment_expires_at',
    ];

    protected function casts(): array
    {
        return ['items' => 'array', 'payment_instructions' => 'array', 'payment_paid_at' => 'datetime', 'payment_expires_at' => 'datetime', 'subtotal' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
