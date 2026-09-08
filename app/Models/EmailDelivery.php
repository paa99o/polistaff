<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailDelivery extends Model
{
    protected $fillable = ['user_id', 'notification_id', 'event', 'recipient', 'mailable', 'status', 'attempts', 'sent_at', 'error'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function notification(): BelongsTo { return $this->belongsTo(PortalNotification::class, 'notification_id'); }
}
