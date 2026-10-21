<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('moderate', $this->route('comment'));
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['approved', 'rejected'])]];
    }
}
