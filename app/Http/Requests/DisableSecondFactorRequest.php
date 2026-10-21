<?php

namespace App\Http\Requests;

class DisableSecondFactorRequest extends CurrentPasswordRequest
{
    public function rules(): array
    {
        return parent::rules() + ['code' => ['required', 'string', 'max:50']];
    }
}
