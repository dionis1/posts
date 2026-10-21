<?php

namespace App\Http\Requests\Api;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('write') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(PostStatus::class)], 'lock_version' => ['required', 'integer', 'min:1'], 'feedback' => ['nullable', 'string', 'max:2000'], 'scheduled_at' => ['nullable', 'date_format:Y-m-d\TH:i:sP', 'after:now']];
    }
}
