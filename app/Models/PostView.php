<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'date', 'visitor_hash'];

    protected $hidden = ['visitor_hash'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
