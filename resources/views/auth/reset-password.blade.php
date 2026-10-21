@extends('layouts.app')
@section('title', __('Set a new password'))
@section('content')
<div class="auth-card card">
    <h1>{{ __('Set a new password') }}</h1>
    <form method="POST" action="{{ route('password.update') }}" class="stack">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email', $email) }}" required>
        <label for="password">{{ __('Password (at least 12 characters)') }}</label>
        <input id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required autofocus>
        <label for="password_confirmation">{{ __('Confirm password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
        <button class="button" type="submit">{{ __('Reset password') }}</button>
    </form>
</div>
@endsection
