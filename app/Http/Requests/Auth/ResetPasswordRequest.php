<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordRules;

class ResetPasswordRequest extends EmailRequest
{
    public function rules(): array
    {
        return parent::rules() + ['token' => ['required', 'string', 'max:255'], 'password' => PasswordRules::confirmed()];
    }
}
