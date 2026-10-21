<?php

namespace App\Http\Requests;

use App\Support\PasswordRules;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['current_password' => ['required', 'string', 'current_password'], 'password' => PasswordRules::confirmed()];
    }
}
