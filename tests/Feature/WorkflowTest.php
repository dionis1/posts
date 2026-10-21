<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Enums\Role;
use App\Jobs\PublishPost;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\WorkflowChanged;
use App\Services\PostService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->service = app(PostService::class);
    }

    private function actor(Role $role = Role::Author): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function draft(User $author): Post
    {
        return $this->service->create($author, ['title' => 'Editorial architecture', 'body' => 'A clear first draft.']);
    }

    private function reviewed(User $author, User $reviewer): Post
    {
        $post = $this->draft($author);
        $post = $this->service->transition($author, $post, 'review', $post->lock_version);

        return $this->service->transition($reviewer, $post, 'approved', $post->lock_version);
    }

    public function test_create_owns_draft_and_ignores_privileged_input(): void
    {
        $author = $this->actor();
        $other = $this->actor();
        $post = $this->service->create($author, [
            'title' => 'My article', 'body' => 'Text', 'user_id' => $other->id,
            'status' => 'published', 'lock_version' => 100, 'published_at' => now(),
        ]);
        $this->assertSame($author->id, $post->user_id);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame(1, $post->lock_version);
        $this->assertNull($post->published_at);
        $this->assertSame('Text', $post->revisions()->first()->content['body']);
        $this->assertDatabaseHas('activities', ['post_id' => $post->id, 'actor_id' => $author->id, 'event' => 'post.created']);
    }

    public function test_author_cannot_read_or_change_another_authors_draft(): void
    {
        $author = $this->actor();
        $stranger = $this->actor();
        $post = $this->draft($author);
        $this->assertFalse(Gate::forUser($stranger)->allows('view', $post));
        $this->assertFalse(Gate::forUser($stranger)->allows('delete', $post));
        $this->assertCount(0, Post::visibleTo($stranger)->get());
        $this->expectException(AuthorizationException::class);
        $this->service->update($stranger, $post, ['body' => 'Stolen'], 1);
    }

    public static function roles(): array
    {
        return array_map(fn (Role $role) => [$role], Role::cases());
    }

    #[DataProvider('roles')]
    public function test_no_role_can_approve_their_own_article(Role $role): void
    {
        $author = $this->actor($role);
        $post = $this->draft($author);
        $post = $this->service->transition($author, $post, 'review', 1);
        $this->assertFalse(Gate::forUser($author)->allows('approve', $post));
        $this->expectException(AuthorizationException::class);
        $this->service->transition($author, $post, 'approved', 2);
    }

    public function test_reviewer_can_approve_another_author_and_editor_can_publish(): void
    {
        $author = $this->actor();
        $reviewer = $this->actor(Role::Reviewer);
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $reviewer);
        $this->assertSame(PostStatus::Approved, $post->status);
        $post = $this->service->transition($editor, $post, 'published', $post->lock_version);
        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertCount(1, Post::published()->get());
        $this->assertSame(4, $post->revisions()->count());
        Notification::assertSentTo($author, WorkflowChanged::class, fn ($notification) => $notification->event === 'post.approved');
        Notification::assertSentTo($reviewer, WorkflowChanged::class, fn ($notification) => $notification->event === 'post.submitted');
    }

    public function test_draft_cannot_skip_review_even_for_admin(): void
    {
        $author = $this->actor();
        $admin = $this->actor(Role::Admin);
        $post = $this->draft($author);
        $this->expectException(ValidationException::class);
        $this->service->transition($admin, $post, 'published', 1);
    }

    public function test_rejection_requires_feedback_and_records_actor_and_reason(): void
    {
        $author = $this->actor();
        $reviewer = $this->actor(Role::Reviewer);
        $post = $this->service->transition($author, $this->draft($author), 'review', 1);
        try {
            $this->service->transition($reviewer, $post, 'draft', 2);
            $this->fail('A rejection without feedback should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('feedback', $exception->errors());
        }
        $this->assertSame(2, $post->fresh()->lock_version);
        $post = $this->service->transition($reviewer, $post, 'draft', 2, 'Please cite the original sources.');
        $this->assertSame(PostStatus::Draft, $post->status);
        $activity = $post->activities()->first();
        $this->assertSame('post.rejected', $activity->event);
        $this->assertSame($reviewer->id, $activity->actor_id);
        $this->assertSame('Please cite the original sources.', $activity->properties['feedback']);
    }

    public function test_author_cannot_edit_article_while_it_is_under_review(): void
    {
        $author = $this->actor();
        $post = $this->service->transition($author, $this->draft($author), 'review', 1);
        $this->expectException(AuthorizationException::class);
        $this->service->update($author, $post, ['body' => 'Changed after submission'], 2);
    }

    public function test_editor_content_change_invalidates_previous_approval(): void
    {
        $author = $this->actor();
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $editor);
        $post = $this->service->update($editor, $post, ['body' => 'Changed content'], 3);
        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertSame(4, $post->lock_version);
        $this->assertFalse(Gate::forUser($editor)->allows('publish', $post));
    }

    public function test_stale_update_cannot_overwrite_a_newer_revision(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        $this->service->update($author, $post, ['title' => 'Latest title'], 1);
        try {
            $this->service->update($author, $post, ['title' => 'Stale title'], 1);
            $this->fail('A stale update should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('lock_version', $exception->errors());
        }
        $this->assertSame('Latest title', $post->fresh()->title);
        $this->assertSame(2, $post->revisions()->count());
        $this->assertSame(2, $post->activities()->count());
    }

    public function test_stale_transition_cannot_apply_to_changed_content(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        $this->service->update($author, $post, ['body' => 'New body'], 1);
        $this->expectException(ValidationException::class);
        $this->service->transition($author, $post, 'review', 1);
    }

    public function test_restore_creates_new_revision_and_restores_taxonomy_and_metadata(): void
    {
        $author = $this->actor();
        $category = Category::create(['name' => 'Engineering', 'slug' => 'engineering']);
        $tag = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);
        $post = $this->service->create($author, [
            'title' => 'Original', 'body' => 'Original body', 'category_id' => $category->id,
            'tags' => [$tag->id], 'seo_title' => 'Original SEO',
        ]);
        $revision = $post->revisions()->first();
        $post = $this->service->update($author, $post, ['title' => 'New title', 'category_id' => null, 'tags' => [], 'seo_title' => 'New SEO'], 1);
        $post = $this->service->restore($author, $post, $revision, 2);
        $this->assertSame('Original', $post->title);
        $this->assertSame('Original SEO', $post->seo_title);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame([$tag->id], $post->tags()->pluck('tags.id')->all());
        $this->assertSame(3, $post->lock_version);
        $this->assertSame(3, $post->revisions()->count());
        $this->assertSame('post.restored', $post->activities()->first()->event);
    }

    public function test_revision_from_another_post_cannot_be_restored(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        $other = $this->draft($author);
        $this->expectException(HttpException::class);
        $this->service->restore($author, $post, $other->revisions()->first(), 1);
    }

    public function test_live_content_must_be_reopened_before_editing(): void
    {
        $author = $this->actor();
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $editor);
        $post = $this->service->transition($editor, $post, 'published', 3);
        $this->assertFalse(Gate::forUser($editor)->allows('update', $post));
        $this->assertFalse(Gate::forUser($editor)->allows('restore', $post));
        $post = $this->service->transition($editor, $post, 'draft', 4);
        $this->assertNull($post->published_at);
        $this->assertCount(0, Post::published()->get());
        $this->assertTrue(Gate::forUser($author)->allows('update', $post));
    }

    public function test_due_job_is_idempotent_and_records_publication_once(): void
    {
        $this->freezeTime();
        $author = $this->actor();
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $editor);
        $post = $this->service->transition($editor, $post, 'scheduled', 3, null, now()->addMinute()->toIso8601String());
        $job = new PublishPost($post->id, 4);
        $this->travel(2)->minutes();
        $job->handle($this->service);
        $job->handle($this->service);
        $this->assertSame(PostStatus::Published, $post->fresh()->status);
        $this->assertSame(5, $post->fresh()->lock_version);
        $this->assertSame(1, $post->activities()->where('event', 'post.published')->count());
        $this->assertSame(1, $post->revisions()->where('version', 5)->count());
        $this->assertCount(1, Notification::sent($author, WorkflowChanged::class, fn ($notification) => $notification->event === 'post.published'));
    }

    public function test_early_and_cancelled_scheduled_jobs_do_nothing(): void
    {
        $author = $this->actor();
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $editor);
        $post = $this->service->transition($editor, $post, 'scheduled', 3, null, now()->addHour()->toIso8601String());
        $this->assertNull($this->service->publishScheduled($post->id, 4));
        $post = $this->service->transition($editor, $post, 'draft', 4);
        $this->travel(2)->hours();
        $this->assertNull($this->service->publishScheduled($post->id, 4));
        $this->assertSame(PostStatus::Draft, $post->fresh()->status);
        $this->assertSame(0, $post->activities()->where('event', 'post.published')->count());
    }

    public function test_past_schedule_fails_without_mutation(): void
    {
        $author = $this->actor();
        $editor = $this->actor(Role::Editor);
        $post = $this->reviewed($author, $editor);
        try {
            $this->service->transition($editor, $post, 'scheduled', 3, null, now()->subMinute()->toIso8601String());
            $this->fail('Scheduling in the past should fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('scheduled_at', $exception->errors());
        }
        $this->assertSame(PostStatus::Approved, $post->fresh()->status);
        $this->assertSame(3, $post->fresh()->lock_version);
    }

    public function test_author_and_reviewer_cannot_publish_or_schedule_approved_content(): void
    {
        $author = $this->actor();
        $reviewer = $this->actor(Role::Reviewer);
        $post = $this->reviewed($author, $reviewer);
        foreach ([$author, $reviewer] as $actor) {
            $this->assertFalse(Gate::forUser($actor)->allows('publish', $post));
            $this->assertFalse(Gate::forUser($actor)->allows('schedule', $post));
        }
        $this->expectException(AuthorizationException::class);
        $this->service->transition($author, $post, 'published', 3);
    }

    public function test_command_only_dispatches_due_scheduled_articles(): void
    {
        Queue::fake();
        $due = Post::factory()->create(['status' => PostStatus::Scheduled, 'scheduled_at' => now()->subMinute()]);
        Post::factory()->create(['status' => PostStatus::Scheduled, 'scheduled_at' => now()->addHour()]);
        Post::factory()->create(['status' => PostStatus::Draft, 'scheduled_at' => now()->subHour()]);
        $this->artisan('posts:publish-due')->assertSuccessful();
        Queue::assertPushed(PublishPost::class, fn ($job) => $job->postId === $due->id && $job->version === 1);
        Queue::assertPushed(PublishPost::class, 1);
    }

    public function test_audit_failure_rolls_back_content_and_revision_atomically(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        Activity::creating(fn () => throw new RuntimeException('Simulated audit failure'));
        try {
            $this->service->update($author, $post, ['title' => 'Must not persist'], 1);
            $this->fail('The simulated failure should propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit failure', $exception->getMessage());
        } finally {
            Activity::flushEventListeners();
        }
        $this->assertSame('Editorial architecture', $post->fresh()->title);
        $this->assertSame(1, $post->fresh()->lock_version);
        $this->assertSame(1, $post->revisions()->count());
    }

    public function test_deleting_draft_keeps_audit_and_does_not_recreate_when_saved(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        $this->service->delete($author, $post, 1);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('activities', ['post_id' => $post->id, 'event' => 'post.deleted']);
        $this->expectException(ModelNotFoundException::class);
        $this->service->update($author, $post, ['body' => 'Resurrected'], 1);
    }

    public function test_full_text_search_tracks_updates_and_handles_query_operators_as_text(): void
    {
        $author = $this->actor();
        $post = $this->service->create($author, ['title' => 'Concurrency control', 'body' => 'Optimistic locking protects editors.']);
        // Verify the FTS index independently of the Eloquent scope.
        $matches = DB::select('SELECT rowid FROM post_search WHERE post_search MATCH ?', ['"optimistic" AND "locking"']);
        $this->assertSame([$post->id], array_map(fn ($row) => $row->rowid, $matches));
        $this->assertSame([$post->id], Post::search('optimistic locking')->pluck('id')->all());
        $this->assertSame([$post->id], Post::search('"optimistic" + locking*')->pluck('id')->all());
        $this->assertSame([], Post::search('missing OR optimistic')->pluck('id')->all());
        $post = $this->service->update($author, $post, ['body' => 'Completely rewritten.'], 1);
        $this->assertCount(0, Post::search('optimistic')->get());
        $this->assertCount(1, Post::search('rewritten')->get());
        $this->service->delete($author, $post, 2);
        $this->assertCount(0, Post::search('rewritten')->get());
    }

    public function test_markdown_strips_raw_html_and_unsafe_links(): void
    {
        $post = new Post(['body' => '<script>alert(1)</script> **Safe** [click](javascript:alert(1))']);
        $html = $post->renderedBody();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringContainsString('<strong>Safe</strong>', $html);
    }

    public function test_duplicate_slug_and_unsafe_canonical_url_are_rejected(): void
    {
        $author = $this->actor();
        $post = $this->draft($author);
        try {
            $this->service->create($author, ['title' => 'Duplicate', 'body' => 'Text', 'slug' => $post->slug, 'canonical_url' => 'javascript:alert(1)']);
            $this->fail('Invalid metadata should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('slug', $exception->errors());
            $this->assertArrayHasKey('canonical_url', $exception->errors());
        }
        $this->assertSame(1, Post::count());
    }
}
