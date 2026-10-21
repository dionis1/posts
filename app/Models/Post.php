<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['title', 'body', 'slug', 'excerpt', 'category_id', 'seo_title', 'seo_description', 'canonical_url', 'cover_media_id', 'locale'];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'lock_version' => 'integer',
            'user_id' => 'integer',
            'category_id' => 'integer',
            'cover_media_id' => 'integer',
            'published_at' => 'immutable_datetime',
            'scheduled_at' => 'immutable_datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->orderByDesc('version');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest('id');
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === Role::Author
            ? $query->where('user_id', $user->id)
            : $query;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)->where('published_at', '<=', now());
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_substr($search ?? '', 0, 200), $matches);
        $terms = array_slice($matches[0], 0, 12);
        if ($terms === []) {
            return $query;
        }

        if ($query->getConnection()->getDriverName() === 'sqlite') {
            $expression = implode(' AND ', array_map(fn (string $term) => '"'.$term.'"', $terms));

            return $query->whereRaw('posts.id IN (SELECT rowid FROM post_search WHERE post_search MATCH ?)', [$expression]);
        }

        // The local application uses SQLite FTS5. Keep other database connections functional.
        foreach ($terms as $term) {
            $query->where(fn (Builder $nested) => $nested->where('title', 'like', '%'.$term.'%')->orWhere('body', 'like', '%'.$term.'%'));
        }

        return $query;
    }

    public function renderedBody(): string
    {
        return Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }
}
