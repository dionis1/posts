<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['post_id', 'user_id', 'version', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array', 'version' => 'integer'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class)->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
