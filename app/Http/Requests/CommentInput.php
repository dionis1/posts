<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommentInput extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Comment::class, $this->route('post')]);
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:3', 'max:3000'], 'parent_id' => ['nullable', 'integer', Rule::exists('comments', 'id')->where('post_id', $this->route('post')->id)->where('status', 'approved')], 'website' => ['nullable', 'string', 'size:0']];
    }
}
