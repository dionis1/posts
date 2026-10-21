<?php

namespace App\Http\Requests\Api;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('read') ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:200'], 'status' => ['nullable', Rule::enum(PostStatus::class)], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'tag_id' => ['nullable', 'integer', 'exists:tags,id'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
