<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Browser test accounts may only be seeded in testing.');
        }

        User::factory()->create([
            'name' => 'Browser Editor',
            'email' => 'editor-browser@example.test',
            'password' => 'browser-test-password-123',
            'role' => Role::Editor,
            'email_verified_at' => now(),
        ]);
    }
}
