@extends('layouts.app')
@section('title', __('Comment moderation'))
@section('content')
<div class="page-heading">
    <h1>{{ __('Comments awaiting review') }}</h1>
</div>
@forelse ($comments as $comment)
    <article class="card card-body">
        <h2>{{ $comment->post?->title }}</h2>
        <p>{{ $comment->body }}</p>
        <p class="muted">{{ $comment->author?->name }}</p>
        @can('moderate', $comment)
        <form method="POST" action="{{ route('comments.moderate', $comment) }}">
            @csrf
            @method('PATCH')
            <button type="submit" name="status" value="approved">{{ __('Approve') }}</button>
            <button type="submit" name="status" value="rejected">{{ __('Reject') }}</button>
        </form>
        @endcan
    </article>
@empty
    <p class="muted">{{ __('No comments are waiting for moderation.') }}</p>
@endforelse
{{ $comments->links() }}
@endsection
