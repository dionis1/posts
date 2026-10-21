<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('post'));
    }

    public function rules(): array
    {
        return ['lock_version' => ['required', 'integer', 'min:1']];
    }
}
