<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityPaperworkVersion extends Model
{
    protected $fillable = ['activity_id', 'generated_by', 'version', 'template_version', 'status', 'content', 'generated_at'];

    protected function casts(): array
    {
        return ['content' => 'array', 'generated_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
