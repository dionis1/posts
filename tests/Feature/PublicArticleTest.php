<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_only_lists_published_articles(): void
    {
        $published = Post::factory()->published()->create(['title' => 'Visible article']);
        Post::factory()->create(['title' => 'Private draft']);

        $this->get('/')->assertOk()
            ->assertSee($published->title)
            ->assertDontSee('Private draft');
    }

    public function test_published_article_exposes_safe_seo_metadata(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'The published story',
            'seo_description' => 'A well-reviewed public article.',
        ]);

        $this->get(route('publication.show', $post->slug))
            ->assertOk()
            ->assertSee($post->title)
            ->assertSee('name="description"', false)
            ->assertSee('A well-reviewed public article.')
            ->assertSee('rel="canonical"', false);
    }

    public function test_verified_reader_can_submit_comment_for_moderation(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);

        $this->actingAs($reader)
            ->post(route('comments.store', $post), ['body' => 'This deserves a follow-up.'])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $reader->id,
            'status' => 'pending',
        ]);

        $this->get(route('publication.show', $post->slug))->assertDontSee('This deserves a follow-up.');
    }

    public function test_guests_cannot_submit_comments(): void
    {
        $post = Post::factory()->published()->create();
        $this->post(route('comments.store', $post), ['body' => 'Guest comment'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('comments', 0);
    }
}
