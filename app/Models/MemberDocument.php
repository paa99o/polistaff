<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberDocument extends Model
{
    protected $fillable = ['user_id', 'title', 'document_type', 'file_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
