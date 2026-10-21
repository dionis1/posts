<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Comment> */
class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory()->published(),
            'user_id' => User::factory(),
            'body' => fake()->paragraph(),
            'depth' => 0,
            'status' => CommentStatus::Pending,
        ];
    }

    public function approved(User $moderator): static
    {
        return $this->state(fn () => [
            'status' => CommentStatus::Approved,
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
    }
}
