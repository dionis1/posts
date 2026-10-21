<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_editorial_api(): void
    {
        $this->getJson('/api/v1/posts')->assertUnauthorized();
    }

    public function test_read_only_token_cannot_create_posts(): void
    {
        $user = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        Sanctum::actingAs($user, ['read']);

        $this->getJson('/api/v1/posts')->assertOk();
        $this->postJson('/api/v1/posts', ['title' => 'A story', 'body' => 'Content', 'locale' => 'en'])->assertForbidden();
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_api_persists_tags_and_seo_fields(): void
    {
        $user = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        $tag = Tag::create(['name' => 'Engineering', 'slug' => 'engineering']);
        Sanctum::actingAs($user, ['read', 'write']);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Clear contracts',
            'body' => 'Build a stable publishing API.',
            'locale' => 'en',
            'tags' => [$tag->id],
            'seo_title' => 'Clean API',
            'seo_description' => 'Professional editorial platform.',
        ]);

        $response->assertCreated()->assertJsonPath('data.title', 'Clear contracts');
        $post = Post::firstOrFail();
        $this->assertSame('Clean API', $post->seo_title);
        $this->assertSame('Professional editorial platform.', $post->seo_description);
        $this->assertSame([$tag->id], $post->tags()->pluck('tags.id')->all());
    }

    public function test_author_cannot_read_another_authors_draft(): void
    {
        $owner = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        $stranger = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        $post = app(\App\Services\PostService::class)->create($owner, ['title' => 'Private draft', 'body' => 'Not public']);

        Sanctum::actingAs($stranger, ['read']);
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonMissing(['title' => 'Private draft']);
        $this->getJson('/api/v1/posts/'.$post->id)->assertForbidden();
    }
}
