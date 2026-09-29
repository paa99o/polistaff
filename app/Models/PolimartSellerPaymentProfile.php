<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolimartSellerPaymentProfile extends Model
{
    protected $fillable = ['user_id', 'qr_code_path', 'bank_name', 'account_name', 'account_number'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
