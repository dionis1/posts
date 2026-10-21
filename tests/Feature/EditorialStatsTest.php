<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\EditorialStats;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditorialStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_sees_only_their_own_draft_count(): void
    {
        $author = User::factory()->create(['role' => Role::Author]);
        $other = User::factory()->create(['role' => Role::Author]);
        Post::factory()->create(['user_id' => $author->id]);
        Post::factory()->create(['user_id' => $other->id]);

        $this->actingAs($author);
        Livewire::test(EditorialStats::class)
            ->assertSee('Drafts')
            ->assertSee('>1<', false);
    }
}
