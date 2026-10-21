<?php

namespace App\Http\Requests\Auth;

class LoginRequest extends EmailRequest
{
    public function rules(): array
    {
        return parent::rules() + ['password' => ['required', 'string', 'max:128'], 'remember' => ['sometimes', 'boolean']];
    }
}
