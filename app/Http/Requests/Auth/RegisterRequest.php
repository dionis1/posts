<?php

namespace App\Http\Requests\Auth;

use App\Support\PasswordRules;

class RegisterRequest extends EmailRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'][] = 'unique:users';

        return $rules + ['name' => ['required', 'string', 'max:120'], 'password' => PasswordRules::confirmed()];
    }
}
