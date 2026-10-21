@extends('layouts.app')
@section('title', __('Verify your email'))
@section('content')
<div class="auth-card card">
    <h1>{{ __('Verify your email') }}</h1>
    <p>{{ __('Please follow the verification link in your email to access the editorial workspace.') }}</p>
    <p class="muted">{{ auth()->user()->email }}</p>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button class="button" type="submit">{{ __('Resend verification email') }}</button>
    </form>
    <p><a href="{{ route('profile.edit') }}">{{ __('Update your email address') }}</a></p>
</div>
@endsection
