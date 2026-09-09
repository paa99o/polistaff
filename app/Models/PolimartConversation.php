<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PolimartConversation extends Model
{
    protected $fillable = ['polimart_item_id', 'buyer_id', 'seller_id', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PolimartItem::class, 'polimart_item_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PolimartMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(PolimartMessage::class)->latestOfMany();
    }
}
