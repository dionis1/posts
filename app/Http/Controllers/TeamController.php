<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('team.index', ['users' => User::query()->orderBy('name')->paginate(20), 'roles' => Role::cases()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        DB::transaction(function () use ($user, $data, $request): void {
            // Lock admins in a stable order so two demotions cannot remove the last one.
            $admins = User::query()->where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            abort_unless($admins->contains('id', $request->user()->id), 403);
            $target = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($target->hasRole('admin') && $data['role'] !== 'admin' && $admins->count() <= 1) {
                throw ValidationException::withMessages(['role' => __('At least one administrator must remain.')]);
            }
            $previous = $target->role->value;
            $target->forceFill(['role' => $data['role']])->save();
            if ($previous !== $data['role']) {
                Activity::create(['actor_id' => $request->user()->id, 'event' => 'user.role_changed', 'properties' => ['user_id' => $target->id, 'from' => $previous, 'to' => $data['role']]]);
            }
        });

        return back()->with('status', __('Team role updated.'));
    }
}
