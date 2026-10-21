<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class RevokeUserSessions
{
    public function handle(User $user): void
    {
        // Password-hash authentication middleware also rejects old non-database sessions.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->tokens()->delete();
    }
}
