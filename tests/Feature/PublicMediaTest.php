<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_read_an_unpublished_image(): void
    {
        Storage::fake('local');
        $image = $this->image();

        $this->get(route('media.show', $image))->assertForbidden();
    }

    public function test_guest_can_read_a_ready_published_cover(): void
    {
        Storage::fake('local');
        $image = $this->image();
        Post::factory()->published()->create([
            'user_id' => $image->user_id,
            'cover_media_id' => $image->id,
        ]);

        $this->get(route('media.show', $image))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    private function image(): Media
    {
        $user = User::factory()->create();
        $path = 'media/processed/example.png';
        Storage::disk('local')->put($path, 'example image bytes');

        return Media::create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'example.png',
            'mime' => 'image/png',
            'size' => 19,
            'width' => 32,
            'height' => 32,
            'status' => 'ready',
        ]);
    }
}
