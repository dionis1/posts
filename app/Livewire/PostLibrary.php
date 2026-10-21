<?php

namespace App\Livewire;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PostLibrary extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);

        return $user;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'category'], true)) {
            $this->resetPage();
            $this->validateOnly($property, $this->filterRules());
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'category']);
        $this->resetPage();
    }

    private function filterRules(): array
    {
        return ['search' => 'nullable|string|max:100', 'status' => ['nullable', Rule::enum(PostStatus::class)], 'category' => 'nullable|integer|exists:categories,id'];
    }

    #[Computed]
    public function posts()
    {
        if (validator($this->only(['search', 'status', 'category']), $this->filterRules())->fails()) {
            return Post::visibleTo($this->actor())->whereRaw('1=0')->paginate(12);
        }

        return Post::visibleTo($this->actor())->with(['author', 'category'])->search($this->search)
            ->when($this->status, fn ($q) => $q->where('status', $this->status))->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->latest('updated_at')->orderByDesc('id')->paginate(12);
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    public function render()
    {
        $this->actor();

        return view('livewire.post-library', ['posts' => $this->posts, 'categories' => $this->categories]);
    }
}
