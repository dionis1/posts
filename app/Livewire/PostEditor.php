<?php

namespace App\Livewire;

use App\Actions\Posts\CreatePostAction;
use App\Actions\Posts\TransitionPostAction;
use App\Actions\Posts\UpdatePostAction;
use App\Enums\MediaStatus;
use App\Livewire\Forms\PostForm;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PostEditor extends Component
{
    public PostForm $form;

    #[Locked]
    public ?int $postId = null;

    #[Locked]
    public ?int $version = null;

    public string $feedback = '';

    public string $scheduledAt = '';

    public bool $showPreview = false;

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->authorize('view', $post);
            $this->postId = $post->id;
            $this->version = $post->lock_version;
            $this->form->loadPost($post);
        } else {
            $this->authorize('create', Post::class);
            $this->form->locale = app()->getLocale();
        }
    }

    private function actor(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->hasVerifiedEmail(), 403);

        return $user;
    }

    #[Computed]
    public function post(): ?Post
    {
        $this->actor();
        if ($this->postId === null) {
            return null;
        }
        $post = Post::findOrFail($this->postId);
        $this->authorize('view', $post);

        return $post;
    }

    #[Computed]
    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    #[Computed]
    public function tags()
    {
        return Tag::orderBy('name')->get();
    }

    #[Computed]
    public function media()
    {
        return Media::where('status', MediaStatus::Ready)->when(! $this->actor()->role->canEditOthers(), fn ($query) => $query->where('user_id', Auth::id()))->latest()->limit(100)->get();
    }

    #[Computed]
    public function preview(): string
    {
        return (new Post(['body' => $this->form->body]))->renderedBody();
    }

    public function save(CreatePostAction $create, UpdatePostAction $update): void
    {
        $actor = $this->actor();
        $post = $this->post;
        $post ? $this->authorize('update', $post) : $this->authorize('create', Post::class);
        $data = $this->form->validatedContent();
        $saved = $post ? $update($actor, $post, $data, $this->version) : $create($actor, $data);
        $isNew = $this->postId === null;
        $this->postId = $saved->id;
        $this->version = $saved->lock_version;
        $this->form->slug = $saved->slug;
        unset($this->post);
        $this->dispatch('post-saved', postId: $saved->id);
        session()->flash('status', __($isNew ? 'Draft created.' : 'Changes saved.'));
        if ($isNew) {
            $this->redirectRoute('posts.edit', ['post' => $saved->id]);
        }
    }

    public function changeStatus(string $target, TransitionPostAction $transition): void
    {
        $this->validate(['feedback' => 'nullable|string|max:2000', 'scheduledAt' => 'nullable|date']);
        $post = $this->post;
        abort_unless($post, 404);
        // Never refresh the expected version before writing: stale drafts must conflict.
        $post = $transition($this->actor(), $post, $target, $this->version, $this->feedback ?: null, $this->scheduledAt ?: null);
        $this->version = $post->lock_version;
        unset($this->post);
        $this->dispatch('workflow-updated', postId: $post->id);
        session()->flash('status', __('Workflow updated.'));
    }

    public function togglePreview(): void
    {
        $this->validate(['form.body' => 'required|string|max:100000']);
        $this->showPreview = ! $this->showPreview;
    }

    public function render()
    {
        $this->actor();

        return view('livewire.post-editor', ['post' => $this->post]);
    }
}
