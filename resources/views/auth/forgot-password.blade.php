@extends('layouts.app')
@section('title', __('Reset your password'))
@section('content')
<div class="auth-card card">
    <h1>{{ __('Reset your password') }}</h1>
    <p class="muted">{{ __('Enter your email and we will send a password reset link.') }}</p>
    <form method="POST" action="{{ route('password.email') }}" class="stack">
        @csrf
        <label for="email">{{ __('Email address') }}</label>
        <input id="email" name="email" type="email" autocomplete="email" value="{{ old('email') }}" required autofocus>
        <button class="button" type="submit">{{ __('Send reset link') }}</button>
    </form>
    <p><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
</div>
@endsection
