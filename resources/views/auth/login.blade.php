@extends('layouts.app')
@section('title', __('Sign in'))
@section('content')
<div class="auth-card card">
    <p class="eyebrow">{{ __('Your editorial workspace') }}</p>
    <h1>{{ __('Welcome back') }}</h1>
    <p class="muted">{{ __('Sign in to write, review, and publish with your team.') }}</p>
    <form method="POST" action="{{ route('login') }}" class="stack">
        @csrf
        <label for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus>
        <label for="password">{{ __('Password') }}</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <label class="checkbox"><input type="checkbox" name="remember" value="1"> {{ __('Remember me') }}</label>
        <button class="button" type="submit">{{ __('Sign in') }}</button>
    </form>
    <p><a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a></p>
    <p>{{ __('New to Posts?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a></p>
</div>
@endsection
