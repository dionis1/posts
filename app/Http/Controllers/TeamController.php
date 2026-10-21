<?php

namespace App\Http\Controllers;

use App\Actions\Team\ChangeUserRoleAction;
use App\Enums\Role;
use App\Http\Requests\ChangeRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        return view('team.index', ['users' => User::query()->orderBy('name')->paginate(20), 'roles' => Role::cases()]);
    }

    public function update(ChangeRoleRequest $request, User $user, ChangeUserRoleAction $changeRole): RedirectResponse
    {
        $changeRole($request->user(), $user, Role::from($request->validated('role')));

        return back()->with('status', __('Team role updated.'));
    }
}
