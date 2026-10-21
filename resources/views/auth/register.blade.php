@extends('layouts.app')
@section('title', __('Create an account'))
@section('content')
<div class="auth-card card">
    <p class="eyebrow">{{ __('Start your next story') }}</p>
    <h1>{{ __('Create an account') }}</h1>
    <p class="muted">{{ __('New accounts join as authors. An administrator manages team roles.') }}</p>
    <form method="POST" action="{{ route('register') }}" class="stack">
        @csrf
        <label for="name">{{ __('Name') }}</label>
        <input id="name" name="name" autocomplete="name" maxlength="120" value="{{ old('name') }}" required autofocus>
        <label for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required>
        <label for="password">{{ __('Password (at least 12 characters)') }}</label>
        <input id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
        <label for="password_confirmation">{{ __('Confirm password') }}</label>
        <input id="password_confirmation" name="password_confirmation" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
        <button class="button" type="submit">{{ __('Create an account') }}</button>
    </form>
    <p>{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Sign in') }}</a></p>
</div>
@endsection
