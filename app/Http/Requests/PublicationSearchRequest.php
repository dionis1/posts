<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicationSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'tag' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
