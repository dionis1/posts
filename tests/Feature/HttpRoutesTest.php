<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HttpRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_are_available_without_authentication(): void
    {
        $this->get('/')->assertOk();
        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    }

    public function test_guests_cannot_open_editorial_pages(): void
    {
        foreach (['/dashboard', '/posts', '/calendar', '/media', '/analytics', '/notifications'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_verified_author_can_open_their_editorial_workspace(): void
    {
        $user = User::factory()->create(['role' => Role::Author, 'email_verified_at' => now()]);
        $this->actingAs($user);

        foreach (['/dashboard', '/posts', '/calendar', '/media', '/analytics', '/notifications'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/taxonomy')->assertForbidden();
    }

    public function test_administrator_can_manage_taxonomy_and_moderation(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin, 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $this->get('/taxonomy')->assertOk();
        $this->get('/comments')->assertOk();
    }
}
