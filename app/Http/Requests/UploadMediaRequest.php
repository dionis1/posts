<?php

namespace App\Http\Requests;

use App\Models\Media;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Media::class);
    }

    public function rules(): array
    {
        return ['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=32,min_height=32,max_width=6000,max_height=6000']];
    }
}
