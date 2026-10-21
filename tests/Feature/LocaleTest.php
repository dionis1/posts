<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_language_selection_persists_across_web_requests(): void
    {
        $this->post(route('locale.update'), ['locale' => 'de'])->assertRedirect();
        $this->get('/')->assertOk()->assertSee('lang="de"', false);
    }

    public function test_authenticated_language_selection_is_saved_to_profile(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'locale' => 'en']);
        $this->actingAs($user)->post(route('locale.update'), ['locale' => 'de'])->assertRedirect();

        $this->assertSame('de', $user->fresh()->locale);
        $this->get('/')->assertOk()->assertSee('lang="de"', false);
    }

    public function test_unlisted_languages_are_rejected(): void
    {
        $this->post(route('locale.update'), ['locale' => 'xx'])->assertSessionHasErrors('locale');
    }
}
