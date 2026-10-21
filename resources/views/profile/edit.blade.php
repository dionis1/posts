@extends('layouts.app')
@section('title', __('Account settings'))
@section('content')
<header class="page-header"><div><p class="eyebrow">{{ __('Your account') }}</p><h1>{{ __('Account settings') }}</h1></div></header>
<div class="settings-grid">
<section class="card">
    <h2>{{ __('Profile') }}</h2>
    <form method="POST" action="{{ route('profile.update') }}" class="stack">
        @csrf @method('PATCH')
        <label for="name">{{ __('Name') }}</label>
        <input id="name" name="name" autocomplete="name" value="{{ old('name', $user->name) }}" maxlength="120" required>
        <label for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $user->email) }}" required>
        <label for="locale">{{ __('Language') }}</label>
        <select id="locale" name="locale"><option value="en" @selected(old('locale', $user->locale) === 'en')>English</option><option value="de" @selected(old('locale', $user->locale) === 'de')>Deutsch</option></select>
        <label for="profile_password">{{ __('Current password') }}</label>
        <input id="profile_password" name="current_password" type="password" autocomplete="current-password" required>
        <button class="button" type="submit">{{ __('Save profile') }}</button>
    </form>
</section>
<section class="card">
    <h2>{{ __('Change password') }}</h2>
    <p class="muted">{{ __('Changing your password signs out your devices and revokes API tokens.') }}</p>
    <form method="POST" action="{{ route('profile.password') }}" class="stack">
        @csrf @method('PUT')
        <label for="current_password">{{ __('Current password') }}</label>
        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
        <label for="password">{{ __('Password (at least 12 characters)') }}</label>
        <input id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
        <label for="password_confirmation">{{ __('Confirm password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
        <button class="button" type="submit">{{ __('Change password') }}</button>
    </form>
</section>
<section class="card">
    <h2>{{ __('Two-factor authentication') }}</h2>
    @if(session('recovery_codes'))
        <div class="notice"><strong>{{ __('Save these recovery codes in a safe place. They are shown only once, and each can be used once.') }}</strong>
            <ul>@foreach(session('recovery_codes') as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
        </div>
    @endif
    @if($user->two_factor_confirmed_at)
        <p>{{ __('Two-factor authentication is enabled.') }}</p>
        <p class="muted">{{ __('Use an authenticator app for every sign-in. Remember me is disabled for protected accounts.') }}</p>
        <form method="POST" action="{{ route('two-factor.disable') }}" class="stack">
            @csrf @method('DELETE')
            <label for="disable_password">{{ __('Current password') }}</label>
            <input id="disable_password" name="current_password" type="password" autocomplete="current-password" required>
            <label for="disable_code">{{ __('Authentication or recovery code') }}</label>
            <input id="disable_code" name="code" autocomplete="one-time-code" required maxlength="50">
            <button class="button danger" type="submit">{{ __('Disable two-factor authentication') }}</button>
        </form>
    @elseif($user->two_factor_secret && session('two_factor_setup_until', 0) > now()->timestamp)
        <p>{{ __('Add this setup key to your authenticator app using a time-based, six-digit code.') }}</p>
        <p><strong>{{ __('Setup key') }}:</strong> <code>{{ $user->two_factor_secret }}</code></p>
        <form method="POST" action="{{ route('two-factor.confirm') }}" class="stack">
            @csrf
            <label for="confirm_code">{{ __('Authentication code') }}</label>
            <input id="confirm_code" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" required maxlength="6">
            <button class="button" type="submit">{{ __('Confirm two-factor authentication') }}</button>
        </form>
    @else
        <p class="muted">{{ __('Protect your account with an authenticator app and single-use recovery codes.') }}</p>
        <form method="POST" action="{{ route('two-factor.setup') }}" class="stack">
            @csrf
            <label for="setup_password">{{ __('Current password') }}</label>
            <input id="setup_password" name="current_password" type="password" autocomplete="current-password" required>
            <button class="button" type="submit">{{ __('Set up two-factor authentication') }}</button>
        </form>
    @endif
</section>
</div>
@endsection
