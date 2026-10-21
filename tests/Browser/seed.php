<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (config('database.connections.sqlite.database') !== '/tmp/posts-browser.sqlite') {
    throw new RuntimeException('Browser fixtures require the disposable browser database.');
}
User::factory()->create([
    'name' => 'Browser editor', 'email' => 'editor-browser@example.test',
    'password' => 'browser-test-password-123', 'role' => Role::Editor, 'email_verified_at' => now(),
]);
