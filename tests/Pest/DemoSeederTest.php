<?php

use App\Enums\PostStatus;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('seeds a repeatable editorial workspace without replacing existing data or sending alerts', function () {
    $existing = Post::factory()->create(['title' => 'My real draft']);
    $notifications = Notification::getFacadeRoot();
    $this->seed(DemoSeeder::class);

    expect(Post::count())->toBe(13)
        ->and(User::where('email', 'like', '%@demo.posts.test')->count())->toBe(4)
        ->and(Comment::count())->toBe(4)
        ->and(Notification::getFacadeRoot())->toBe($notifications);
    foreach (PostStatus::cases() as $status) {
        expect(Post::where('slug', 'like', 'demo-%')->where('status', $status)->count())->toBe(2);
    }
    $author = User::where('email', 'author@demo.posts.test')->firstOrFail();
    expect(Hash::check(DemoSeeder::PASSWORD, $author->password))->toBeTrue();
    foreach (Activity::where('event', 'post.approved')->with('post')->get() as $approval) {
        expect($approval->actor_id)->not->toBe($approval->post->user_id);
    }
    $counts = [PostRevision::count(), Activity::count(), User::count()];
    $demoDraft = Post::where('slug', 'demo-planning-the-next-quarter-of-stories')->firstOrFail();
    $demoDraft->update(['title' => 'My edited demo draft']);
    $this->seed(DemoSeeder::class);

    expect([PostRevision::count(), Activity::count(), User::count()])->toBe($counts)
        ->and(Post::count())->toBe(13)
        ->and(Comment::count())->toBe(4)
        ->and($existing->fresh()->title)->toBe('My real draft')
        ->and($demoDraft->fresh()->title)->toBe('My edited demo draft');
});

it('refuses demo credentials outside local and testing environments', function () {
    $this->app->instance('env', 'production');

    expect(fn () => app(DemoSeeder::class)())->toThrow(RuntimeException::class, 'only available')
        ->and(User::count())->toBe(0);
});

it('rolls back instead of promoting an existing account with a conflicting demo email', function () {
    $user = User::factory()->create(['email' => 'editor@demo.posts.test', 'name' => 'Existing user']);

    expect(fn () => $this->seed(DemoSeeder::class))->toThrow(RuntimeException::class, 'different role')
        ->and(User::count())->toBe(1)
        ->and($user->fresh()->name)->toBe('Existing user')
        ->and($user->fresh()->role->value)->toBe('author')
        ->and(Post::count())->toBe(0);
});
