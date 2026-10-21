<?php

namespace Database\Seeders;

use App\Actions\Posts\CreatePostAction;
use App\Actions\Posts\TransitionPostAction;
use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public const PASSWORD = 'PostsDemo!2026';

    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new RuntimeException('Demo data is only available in local and testing environments.');
        }

        // Exercise real workflow actions without sending demo email or queuing alerts.
        $notifications = Notification::getFacadeRoot();
        Notification::fake();

        try {
            DB::transaction(function (): void {
                $users = [];
                foreach (['admin' => 'Alex Morgan', 'editor' => 'Sam Rivera', 'reviewer' => 'Jordan Lee', 'author' => 'Taylor Brooks'] as $role => $name) {
                    $user = User::firstOrCreate(['email' => $role.'@demo.posts.test'], [
                        'name' => $name, 'role' => $role, 'email_verified_at' => now(),
                        'password' => self::PASSWORD, 'locale' => 'en',
                    ]);
                    if ($user->role->value !== $role) {
                        throw new RuntimeException('A demo email belongs to an account with a different role; no data was changed.');
                    }
                    $users[$role] = $user;
                }

                $categories = collect(['Engineering', 'Product', 'Company'])->mapWithKeys(fn ($name) => [
                    $name => Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]),
                ]);
                $tags = collect(['Laravel', 'Editorial', 'Accessibility', 'Research'])->map(fn ($name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id
                )->all();

                $stories = [
                    ['Designing a reliable editorial workflow', 'Engineering', 'published'],
                    ['What we learned from listening to our readers', 'Product', 'published'],
                    ['A practical guide to accessible publishing', 'Engineering', 'scheduled'],
                    ['Inside our next product release', 'Product', 'scheduled'],
                    ['Building trust through transparent updates', 'Company', 'approved'],
                    ['The small details that make content useful', 'Product', 'approved'],
                    ['Safer Markdown for an open publishing platform', 'Engineering', 'review'],
                    ['Meet the people behind the publication', 'Company', 'review'],
                    ['Planning the next quarter of stories', 'Company', 'draft'],
                    ['Measuring the impact of a better reading experience', 'Product', 'draft'],
                    ['Our first editorial playbook', 'Company', 'archived'],
                    ['Lessons from the original publishing prototype', 'Engineering', 'archived'],
                ];

                foreach ($stories as $index => [$title, $category, $status]) {
                    $slug = 'demo-'.Str::slug($title);
                    $existing = Post::withTrashed()->where('slug', $slug)->first();
                    if ($existing) {
                        if ($existing->user_id !== $users['author']->id) {
                            throw new RuntimeException('A demo slug belongs to another author; no data was changed.');
                        }

                        continue;
                    }
                    $excerpt = 'A field guide from our editorial team: practical decisions, thoughtful collaboration, and lessons to carry into the next story.';
                    $post = app(CreatePostAction::class)($users['author'], [
                        'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt,
                        'body' => "## Start with the reader\n\n{$excerpt}\n\nGood publishing begins with a clear question: what should someone understand or be able to do after reading this article? We use that question to shape our research and our review.\n\n## Make each handoff clear\n\n- Give the author space to develop an evidence-based draft.\n- Ask an independent reviewer to check the claims and the structure.\n- Let an editor choose the right moment to publish.\n\n> A useful story is one that helps the reader take their next step.\n\n## What happens next\n\nWe will collect feedback, revisit our assumptions, and improve this guide as the team learns. Every revision keeps a record of the decisions behind the final article.",
                        'category_id' => $categories[$category]->id,
                        'tags' => [$tags[$index % count($tags)], $tags[($index + 1) % count($tags)]],
                        'locale' => 'en', 'seo_title' => $title, 'seo_description' => 'Practical publishing lessons from the Posts editorial team.',
                    ]);
                    $transition = app(TransitionPostAction::class);
                    if ($status !== 'draft') {
                        $post = $transition($users['author'], $post, 'review', $post->lock_version);
                    }
                    if (in_array($status, ['approved', 'scheduled', 'published', 'archived'], true)) {
                        $post = $transition($users['reviewer'], $post, 'approved', $post->lock_version);
                    }
                    if ($status === 'scheduled') {
                        $post = $transition($users['editor'], $post, 'scheduled', $post->lock_version, scheduledAt: now()->addDays($index + 1)->setTime(10, 0)->toDateTimeString());
                    }
                    if (in_array($status, ['published', 'archived'], true)) {
                        $post = $transition($users['editor'], $post, 'published', $post->lock_version);
                    }
                    if ($status === 'archived') {
                        $post = $transition($users['editor'], $post, 'archived', $post->lock_version);
                    }
                    if ($post->status === PostStatus::Published) {
                        Comment::factory()->approved($users['editor'])->create([
                            'post_id' => $post->id, 'user_id' => $users['reviewer']->id,
                            'body' => 'The clear handoffs are especially helpful. We will use this approach in our next editorial planning session.',
                        ]);
                        Comment::factory()->create([
                            'post_id' => $post->id, 'user_id' => $users['author']->id,
                            'body' => 'Thank you for reading. What would you like us to explore next?',
                        ]);
                        foreach (range(1, 8) as $visitor) {
                            PostView::create(['post_id' => $post->id, 'date' => today(), 'visitor_hash' => hash('sha256', "demo-{$index}-{$visitor}")]);
                        }
                    }
                }
            });
        } finally {
            Notification::swap($notifications);
        }
    }
}
