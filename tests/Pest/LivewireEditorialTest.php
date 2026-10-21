<?php

use App\Actions\Posts\CreatePostAction;
use App\Enums\PostStatus;
use App\Enums\Role;
use App\Livewire\PostEditor;
use App\Livewire\PostLibrary;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->author = User::factory()->create(['role' => Role::Author]);
    $this->actingAs($this->author);
});

it('validates the form without persisting invalid content', function () {
    Livewire::test(PostEditor::class)->set('form.title', '   ')->set('form.body', '')->call('save')
        ->assertHasErrors(['form.title' => 'required', 'form.body' => 'required']);
    expect(Post::count())->toBe(0);
});

it('creates an owned draft and emits the save event', function () {
    Livewire::test(PostEditor::class)->set('form.title', 'Livewire editorial')->set('form.body', 'A real draft')->set('form.slug', 'livewire-editorial')->call('save')
        ->assertHasNoErrors()->assertDispatched('post-saved')->assertRedirect(route('posts.edit', 1));
    $post = Post::firstOrFail();
    expect($post->user_id)->toBe($this->author->id)->and($post->status)->toBe(PostStatus::Draft)->and($post->revisions()->count())->toBe(1);
});

it('updates a draft while preserving an unsaved draft on conflict', function () {
    $post = app(CreatePostAction::class)($this->author, ['title' => 'Original', 'body' => 'Original body']);
    $first = Livewire::test(PostEditor::class, ['post' => $post]);
    $second = Livewire::test(PostEditor::class, ['post' => $post]);
    $first->set('form.title', 'Saved first')->call('save')->assertHasNoErrors();
    $second->set('form.title', 'My unsaved draft')->call('save')->assertHasErrors('lock_version')->assertSet('form.title', 'My unsaved draft');
    expect($post->fresh()->title)->toBe('Saved first');
});

it('cannot open another authors draft or bypass locked identity', function () {
    $foreign = Post::factory()->create();
    Livewire::test(PostEditor::class, ['post' => $foreign])->assertForbidden();
    expect(fn () => Livewire::test(PostEditor::class)->set('postId', $foreign->id))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

it('renders sanitized preview without persisting content', function () {
    Livewire::test(PostEditor::class)->set('form.body', 'Hello **editor** <script>alert(1)</script>')->call('togglePreview')
        ->assertSet('showPreview', true)->assertSee('<strong>editor</strong>', false)->assertDontSee('<script>alert(1)</script>', false);
    expect(Post::count())->toBe(0);
});

it('rejects self approval even for administrators', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin);
    $post = app(CreatePostAction::class)($admin, ['title' => 'Needs another reviewer', 'body' => 'Review me']);
    $editor = Livewire::test(PostEditor::class, ['post' => $post]);
    $editor->call('changeStatus', 'review')->assertDispatched('workflow-updated');
    $editor->call('changeStatus', 'approved')->assertForbidden();
    expect($post->fresh()->status)->toBe(PostStatus::Review);
});

it('rechecks authorization after the account loses edit permissions', function () {
    $editor = User::factory()->create(['role' => Role::Editor]);
    $post = Post::factory()->create();
    $this->actingAs($editor);
    $component = Livewire::test(PostEditor::class, ['post' => $post]);
    $editor->forceFill(['role' => Role::Author])->save();
    $component->set('form.title', 'Forbidden change')->assertForbidden();
    expect($post->fresh()->title)->not->toBe('Forbidden change');
});

it('filters scoped results and resets pagination when searching', function () {
    Post::factory()->count(13)->create(['user_id' => $this->author->id, 'title' => 'Editorial notes']);
    Post::factory()->create(['user_id' => $this->author->id, 'title' => 'Distinctive needle']);
    $foreign = Post::factory()->create(['title' => 'Private foreign draft']);
    Livewire::test(PostLibrary::class)->call('setPage', 2)->set('search', 'Distinctive')->assertSee('Distinctive needle')
        ->assertDontSee($foreign->title)->assertViewHas('posts', fn ($posts) => $posts->currentPage() === 1 && $posts->total() === 1)
        ->call('clearFilters')->assertSet('search', '')->assertViewHas('posts', fn ($posts) => $posts->total() === 14);
});

it('validates filter state supplied by clients', function () {
    Livewire::test(PostLibrary::class)->set('search', str_repeat('x', 101))->assertHasErrors('search');
});

it('runs the statistics aggregation only once when loading the library', function () {
    DB::enableQueryLog();
    $this->get('/posts')->assertOk();
    $aggregations = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'count(*) as total') && str_contains($query['query'], 'group by'));
    expect($aggregations)->toHaveCount(1);
    DB::disableQueryLog();
});
