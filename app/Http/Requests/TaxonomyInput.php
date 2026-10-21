<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class TaxonomyInput extends FormRequest
{
    public function authorize(): bool
    {
        abort_unless(in_array($this->route('type'), ['categories', 'tags'], true), 404);

        return $this->user()->can('create', $this->route('type') === 'categories' ? Category::class : Tag::class);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:'.($this->route('type') === 'categories' ? 80 : 50)]];
    }
}
