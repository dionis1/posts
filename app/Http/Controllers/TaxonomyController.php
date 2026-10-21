<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaxonomyController extends Controller
{
    private function authorizeManager(Request $request): void
    {
        abort_unless(in_array($request->user()->role, [Role::Admin, Role::Editor], true), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeManager($request);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('taxonomy.index', compact('categories', 'tags'));
    }

    public function store(Request $request, string $type)
    {
        $this->authorizeManager($request);
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $slug = Str::slug($data['name']);
        validator(['slug' => $slug], ['slug' => ['required', 'max:150', Rule::unique($type, 'slug')]])->validate();
        $class = $type === 'categories' ? Category::class : Tag::class;
        $class::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('status', __('Taxonomy created.'));
    }

    public function destroy(Request $request, string $type, int $id)
    {
        $this->authorizeManager($request);
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        $class = $type === 'categories' ? Category::class : Tag::class;
        $class::findOrFail($id)->delete();

        return back()->with('status', __('Taxonomy deleted.'));
    }
}
