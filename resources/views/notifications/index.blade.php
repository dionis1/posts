@extends('layouts.app')
@section('title', __('Notifications'))
@section('content')
<div class="page-heading"><h1>{{ __('Notifications') }}</h1></div>
@forelse ($notifications as $notification)
<article class="card card-body">
    <p>{{ $notification->data['message'] ?? $notification->data['event'] ?? __('Editorial update') }}</p>
    <p class="muted">{{ $notification->created_at?->diffForHumans() }}</p>
    @if ($notification->read_at === null)
    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
        @csrf
        <button type="submit">{{ __('Mark as read') }}</button>
    </form>
    @endif
</article>
@empty
<p class="muted">{{ __('No notifications yet.') }}</p>
@endforelse
{{ $notifications->links() }}
@endsection
