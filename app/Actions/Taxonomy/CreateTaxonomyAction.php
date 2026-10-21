<?php

namespace App\Actions\Taxonomy;

use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CreateTaxonomyAction
{
    public function __invoke(User $actor, string $type, string $name): Category|Tag
    {
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        $class = $type === 'categories' ? Category::class : Tag::class;
        Gate::forUser($actor)->authorize('create', $class);
        $data = validator(['name' => trim($name), 'slug' => Str::slug($name)], ['name' => ['required', 'string', 'max:'.($type === 'categories' ? 80 : 50)], 'slug' => ['required', 'max:100', Rule::unique($type, 'slug')]])->validate();

        return $class::create($data);
    }
}
