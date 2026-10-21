<?php

namespace App\Http\Controllers;

use App\Actions\Taxonomy\CreateTaxonomyAction;
use App\Http\Requests\TaxonomyInput;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Support\Facades\Gate;

class TaxonomyController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        return view('taxonomy.index', ['categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get()]);
    }

    public function store(TaxonomyInput $request, string $type, CreateTaxonomyAction $create)
    {
        $create($request->user(), $type, $request->validated('name'));

        return back()->with('status', __('Taxonomy created.'));
    }

    public function destroy(string $type, int $id)
    {
        abort_unless(in_array($type, ['categories', 'tags'], true), 404);
        $class = $type === 'categories' ? Category::class : Tag::class;
        $taxonomy = $class::findOrFail($id);
        Gate::authorize('delete', $taxonomy);
        $taxonomy->delete();

        return back()->with('status', __('Taxonomy deleted.'));
    }
}
