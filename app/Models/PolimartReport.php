<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolimartReport extends Model
{
    protected $fillable = [
        'polimart_item_id',
        'reporter_id',
        'reason',
        'details',
        'status',
        'reviewed_by',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(PolimartItem::class, 'polimart_item_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
