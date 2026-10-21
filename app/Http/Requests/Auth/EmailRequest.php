<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($email = $this->input('email'))) {
            $this->merge(['email' => strtolower(trim($email))]);
        }
    }

    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:254', 'not_regex:/[\\x00-\\x20\\x7f]/']];
    }
}
