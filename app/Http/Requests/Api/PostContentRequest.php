<?php

namespace App\Http\Requests\Api;

use App\Support\PostContentRules;
use Illuminate\Foundation\Http\FormRequest;

class PostContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('write') ?? false;
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return PostContentRules::for($post?->id) + ($post ? ['lock_version' => ['required', 'integer', 'min:1']] : []);
    }
}
