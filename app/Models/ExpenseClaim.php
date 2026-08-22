<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseClaim extends Model
{
    protected $fillable = ['user_id', 'reviewed_by', 'treasurer_verified_by', 'transaction_id', 'title', 'description', 'amount', 'category', 'claim_date', 'receipt_path', 'status', 'treasurer_notes', 'treasurer_verified_at', 'review_notes', 'reviewed_at'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'claim_date' => 'date',
            'treasurer_verified_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function treasurerVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'treasurer_verified_by');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
