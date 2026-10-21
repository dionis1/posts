<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\Post> */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence();

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'body' => fake()->paragraphs(3, true),
            'slug' => Str::slug($title).'-'.Str::random(8),
            'status' => PostStatus::Draft,
            'lock_version' => 1,
            'locale' => 'en',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => PostStatus::Published, 'published_at' => now()->subHour()]);
    }
}
