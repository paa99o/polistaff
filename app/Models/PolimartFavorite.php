<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolimartFavorite extends Model
{
    protected $fillable = ['user_id', 'polimart_item_id'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PolimartItem::class, 'polimart_item_id');
    }
}
