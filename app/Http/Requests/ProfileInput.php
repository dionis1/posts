<?php

namespace App\Http\Requests;

use App\Http\Requests\Auth\EmailRequest;
use Illuminate\Validation\Rule;

class ProfileInput extends EmailRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['email'][] = Rule::unique('users')->ignore($this->user()->id);

        return $rules + ['name' => ['required', 'string', 'max:120'], 'locale' => ['required', Rule::in(['en', 'de'])], 'current_password' => ['required', 'string', 'current_password']];
    }
}
