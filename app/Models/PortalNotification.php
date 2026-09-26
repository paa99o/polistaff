<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'title', 'message', 'type', 'is_read', 'link', 'email_recipient', 'email_status', 'email_attempts', 'email_sent_at', 'email_error'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'email_sent_at' => 'datetime'];
    }

    public function getLinkAttribute(?string $value): ?string
    {
        if (! $value) {
            return $value;
        }

        $parts = parse_url($value);
        if (! is_array($parts)) {
            return $value;
        }

        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true) || ! app()->bound('request')) {
            return $value;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return request()->getSchemeAndHttpHost().$path.$query.$fragment;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
