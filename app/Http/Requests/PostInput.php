<?php

namespace App\Http\Requests;

use App\Support\PostContentRules;
use Illuminate\Foundation\Http\FormRequest;

class PostInput extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return PostContentRules::for($this->route('post')?->id) + [
            'lock_version' => [$this->isMethod('PUT') ? 'required' : 'sometimes', 'integer', 'min:1'],
        ];
    }
}
