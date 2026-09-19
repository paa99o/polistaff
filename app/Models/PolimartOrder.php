<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolimartOrder extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'address_line_1', 'address_line_2', 'city', 'postcode', 'state',
        'note', 'items', 'subtotal', 'shipping_fee', 'total', 'status',
    ];

    protected function casts(): array
    {
        return ['items' => 'array', 'subtotal' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'total' => 'decimal:2'];
    }
}
