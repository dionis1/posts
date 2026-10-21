<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkdownPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:100000']];
    }
}
