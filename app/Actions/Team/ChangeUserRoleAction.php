<?php

namespace App\Actions\Team;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ChangeUserRoleAction
{
    public function __invoke(User $actor, User $user, Role $role): void
    {
        Gate::forUser($actor)->authorize('updateRole', $user);
        DB::transaction(function () use ($actor, $user, $role) {
            $admins = User::where('role', Role::Admin)->orderBy('id')->lockForUpdate()->get();
            abort_unless($admins->contains('id', $actor->id), 403);
            $target = User::lockForUpdate()->findOrFail($user->id);
            if ($target->role === Role::Admin && $role !== Role::Admin && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => __('At least one administrator must remain.')]);
            }
            $previous = $target->role;
            if ($previous === $role) {
                return;
            }
            $target->forceFill(['role' => $role])->save();
            Activity::create(['actor_id' => $actor->id, 'event' => 'user.role_changed', 'properties' => ['user_id' => $target->id, 'from' => $previous->value, 'to' => $role->value]]);
        }, 3);
    }
}
