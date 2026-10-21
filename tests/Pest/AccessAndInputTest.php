<?php

use App\Actions\Comments\ModerateCommentAction;
use App\Enums\CommentStatus;
use App\Enums\PostStatus;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('rejects malformed credential types through validation', function () {
    $this->post('/login', ['email' => ['malformed'], 'password' => 'test-password'])->assertSessionHasErrors('email');
    $this->post('/register', ['name' => 'Test', 'email' => 'valid@example.test', 'password' => str_repeat('😀', 19), 'password_confirmation' => str_repeat('😀', 19)])->assertSessionHasErrors('password');
    expect(User::count())->toBe(0);
});

it('applies taxonomy policies and database-sized validation rules', function () {
    $author = User::factory()->create(['role' => Role::Author]);
    $this->actingAs($author)->post('/taxonomy/categories', ['name' => 'Forbidden'])->assertForbidden();
    $editor = User::factory()->create(['role' => Role::Editor]);
    $this->actingAs($editor)->post('/taxonomy/tags', ['name' => str_repeat('x', 51)])->assertSessionHasErrors('name');
    $this->post('/taxonomy/categories', ['name' => 'Editorial craft'])->assertSessionHasNoErrors();
    expect(Category::where('slug', 'editorial-craft')->exists())->toBeTrue();
});

it('rolls back moderation when audit persistence fails', function () {
    $moderator = User::factory()->create(['role' => Role::Editor]);
    $post = Post::factory()->create(['status' => PostStatus::Published, 'published_at' => now()->subMinute()]);
    $comment = Comment::create(['post_id' => $post->id, 'user_id' => $moderator->id, 'body' => 'A comment', 'status' => CommentStatus::Pending]);
    Event::listen('eloquent.creating: '.Activity::class, fn () => throw new RuntimeException('Audit unavailable'));
    try {
        app(ModerateCommentAction::class)($moderator, $comment, CommentStatus::Approved);
        test()->fail('The audit failure must abort moderation.');
    } catch (RuntimeException $error) {
        expect($error->getMessage())->toBe('Audit unavailable');
    }
    expect($comment->fresh()->status)->toBe(CommentStatus::Pending);
});

it('rejects descendants atomically when their parent is rejected', function () {
    $moderator = User::factory()->create(['role' => Role::Editor]);
    $post = Post::factory()->create();
    $parent = Comment::create(['post_id' => $post->id, 'user_id' => $moderator->id, 'body' => 'Parent', 'status' => CommentStatus::Approved]);
    $child = Comment::create(['post_id' => $post->id, 'user_id' => $moderator->id, 'body' => 'Child', 'status' => CommentStatus::Approved, 'parent_id' => $parent->id, 'depth' => 1]);
    app(ModerateCommentAction::class)($moderator, $parent, CommentStatus::Rejected);
    expect($parent->fresh()->status)->toBe(CommentStatus::Rejected)->and($child->fresh()->status)->toBe(CommentStatus::Rejected);
});
