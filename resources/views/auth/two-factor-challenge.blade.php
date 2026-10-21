@extends('layouts.app')
@section('title', __('Two-factor authentication'))
@section('content')
<div class="auth-card card">
    <h1>{{ __('Two-factor authentication') }}</h1>
    <p>{{ __('Enter the current code from your authenticator app or one of your recovery codes.') }}</p>
    <form method="POST" action="{{ route('two-factor.challenge') }}" class="stack">
        @csrf
        <label for="code">{{ __('Authentication or recovery code') }}</label>
        <input id="code" name="code" autocomplete="one-time-code" required autofocus maxlength="50">
        <button class="button" type="submit">{{ __('Verify and sign in') }}</button>
    </form>
    <p><a href="{{ route('login') }}">{{ __('Back to sign in') }}</a></p>
</div>
@endsection
