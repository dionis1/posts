<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LocaleRequest;
use App\Http\Requests\ProfileInput;
use App\Services\RevokeUserSessions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(ProfileInput $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        unset($data['current_password']);
        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $user->save();
            $user->sendEmailVerificationNotification();
        } else {
            $user->save();
        }

        return back()->with('status', __('Profile updated.'));
    }

    public function password(ChangePasswordRequest $request, RevokeUserSessions $sessions): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $sessions->handle($user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Password updated. Please sign in again on your devices.'));
    }

    public function locale(LocaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $request->session()->put('locale', $data['locale']);
        $request->user()?->update($data);

        return back();
    }
}
