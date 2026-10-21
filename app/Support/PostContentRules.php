<?php

namespace App\Support;

use Illuminate\Validation\Rule;

final class PostContentRules
{
    public static function for(?int $postId = null): array
    {
        return [
            'title' => ['required', 'string', 'max:180'], 'body' => ['required', 'string', 'max:100000'],
            'slug' => ['nullable', 'string', 'max:180', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($postId)],
            'excerpt' => ['nullable', 'string', 'max:500'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'tags' => ['sometimes', 'array', 'max:20'], 'tags.*' => ['integer', 'distinct', 'exists:tags,id'],
            'cover_media_id' => ['nullable', 'integer', 'exists:media,id'], 'locale' => ['required', Rule::in(['en', 'de'])],
            'seo_title' => ['nullable', 'string', 'max:60'], 'seo_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
