<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmSecondFactorRequest;
use App\Http\Requests\CurrentPasswordRequest;
use App\Http\Requests\DisableSecondFactorRequest;
use App\Http\Requests\SecondFactorRequest;
use App\Models\User;
use App\Services\RevokeUserSessions;
use App\Services\VerifySecondFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function setup(CurrentPasswordRequest $request, Google2FA $totp): RedirectResponse
    {
        abort_if($request->user()->two_factor_confirmed_at, 409);
        $request->user()->forceFill([
            'two_factor_secret' => $totp->generateSecretKey(),
            'two_factor_last_used' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
        $request->session()->put('two_factor_setup_until', now()->addMinutes(15)->timestamp);

        return back()->with('status', __('Enter the setup key in your authenticator app, then confirm a code.'));
    }

    public function confirm(ConfirmSecondFactorRequest $request, VerifySecondFactor $verification, RevokeUserSessions $sessions): RedirectResponse
    {
        $data = $request->validated();
        abort_if($request->user()->two_factor_confirmed_at, 409);
        abort_unless($request->session()->get('two_factor_setup_until', 0) > now()->timestamp, 403);
        if (! $verification->handle($request->user(), $data['code'])) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid or has already been used.')]);
        }
        $codes = collect(range(1, 8))->map(fn () => Str::random(10).'-'.Str::random(10))->all();
        $user = $request->user();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'remember_token' => Str::random(60),
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $codes),
        ])->save();
        $sessions->handle($user);
        $request->session()->forget('two_factor_setup_until');
        $request->session()->regenerate();

        return back()->with('recovery_codes', $codes)->with('status', __('Two-factor authentication enabled. Save your recovery codes now.'));
    }

    public function disable(DisableSecondFactorRequest $request, VerifySecondFactor $verification, RevokeUserSessions $sessions): RedirectResponse
    {
        $data = $request->validated();
        if (! $verification->handle($request->user(), $data['code'])) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid or has already been used.')]);
        }
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_used' => null,
            'two_factor_recovery_codes' => null,
            'remember_token' => Str::random(60),
        ])->save();
        $sessions->handle($user);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', __('Two-factor authentication disabled. Please sign in again.'));
    }

    public function challengeForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function challenge(SecondFactorRequest $request, VerifySecondFactor $verification): RedirectResponse
    {
        $data = $request->validated();
        $user = User::query()->find($request->session()->get('two_factor_user_id'));
        if (! $user || ! $user->two_factor_confirmed_at
            || $request->session()->get('two_factor_expires_at', 0) <= now()->timestamp
            || ! hash_equals($user->password, (string) $request->session()->get('two_factor_password_hash'))) {
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => __('Please sign in again.')]);
        }
        if (! $verification->handle($user, $data['code'])) {
            throw ValidationException::withMessages(['code' => __('The authentication code is invalid or has already been used.')]);
        }
        // Remember-me cookies would skip the second factor on later logins.
        Auth::login($user, false);
        $request->session()->forget(['two_factor_user_id', 'two_factor_expires_at', 'two_factor_password_hash']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
