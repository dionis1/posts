<?php

namespace App\Http\Requests;

class ConfirmSecondFactorRequest extends CurrentPasswordRequest
{
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']];
    }
}
