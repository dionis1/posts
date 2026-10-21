<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_cannot_assign_administrator_permissions(): void
    {
        $author = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        $target = User::factory()->create(['role' => Role::Author]);

        $this->actingAs($author)->patch(route('team.update', $target), ['role' => 'admin'])
            ->assertForbidden();

        $this->assertSame(Role::Author, $target->fresh()->role);
    }

    public function test_last_administrator_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);

        $this->actingAs($admin)->patch(route('team.update', $admin), ['role' => 'author'])
            ->assertSessionHasErrors('role');

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_administrator_can_assign_reviewers_with_an_audit_record(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);
        $member = User::factory()->create(['role' => Role::Author]);

        $this->actingAs($admin)->patch(route('team.update', $member), ['role' => 'reviewer'])
            ->assertRedirect();

        $this->assertSame(Role::Reviewer, $member->fresh()->role);
        $this->assertDatabaseHas('activities', [
            'actor_id' => $admin->id,
            'event' => 'user.role_changed',
        ]);
    }
}
