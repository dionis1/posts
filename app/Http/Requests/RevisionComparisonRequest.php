<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RevisionComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('post'));
    }

    public function rules(): array
    {
        $revision = ['nullable', 'integer', Rule::exists('post_revisions', 'id')->where('post_id', $this->route('post')->id)];

        return ['left' => $revision, 'right' => $revision, 'page' => ['nullable', 'integer', 'min:1']];
    }
}
