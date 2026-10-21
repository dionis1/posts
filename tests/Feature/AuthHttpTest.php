<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_unverified_author(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ada Writer',
            'email' => 'ada@example.test',
            'password' => 'long-safe-password-123',
            'password_confirmation' => 'long-safe-password-123',
        ]);

        $user = User::firstOrFail();
        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame(Role::Author, $user->role);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('long-safe-password-123', $user->password));
    }

    public function test_verified_user_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'long-safe-password-123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'long-safe-password-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_unverified_user_is_redirected_from_editorial_workspace(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_valid_password_reset_token_changes_credentials(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'another-safe-password-123',
            'password_confirmation' => 'another-safe-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('another-safe-password-123', $user->fresh()->password));
    }
}
