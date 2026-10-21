<?php

namespace App\Http\Requests;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('post'));
    }

    public function rules(): array
    {
        return ['target' => ['required', Rule::enum(PostStatus::class)], 'lock_version' => ['required', 'integer', 'min:1'], 'feedback' => ['nullable', 'string', 'max:2000'], 'scheduled_at' => ['nullable', 'date']];
    }
}
