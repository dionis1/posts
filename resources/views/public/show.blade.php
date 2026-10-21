@extends('layouts.app')
@section('title', $post->title)
@section('meta')
<meta name="description" content="{{ $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($post->body), 155) }}">
<link rel="canonical" href="{{ $post->canonical_url ?: route('publication.show', $post->slug) }}">
@endsection
@section('content')
<article class="card card-body">
    <a href="{{ route('publication.index') }}">← {{ __('All articles') }}</a>
    <h1>{{ $post->title }}</h1>
    @if ($post->coverMedia?->status === 'ready')
        <img class="article-cover" src="{{ route('media.show', $post->coverMedia) }}" alt="{{ $post->title }}" loading="lazy">
    @endif
    <p class="muted">{{ $post->author?->name }} · {{ $post->published_at?->format('Y-m-d') }}</p>
    <div class="article-body">{!! $post->renderedBody() !!}</div>
</article>
<section aria-labelledby="comments-heading">
    <h2 id="comments-heading">{{ __('Comments') }}</h2>
@auth
    @if (auth()->user()->hasVerifiedEmail())
        <form method="POST" action="{{ route('comments.store', $post) }}" class="card card-body stack">
            @csrf
            <label for="comment-body">{{ __('Your comment') }}</label>
            <textarea name="body" id="comment-body" rows="4" minlength="3" maxlength="3000" required>{{ old('body') }}</textarea>
            <button type="submit">{{ __('Submit for review') }}</button>
        </form>
    @endif
@else
    <p><a href="{{ route('login') }}">{{ __('Sign in to comment') }}</a></p>
@endauth
@if ($comments->isNotEmpty())
    @foreach ($comments as $comment)
        <article class="card card-body">
            <p>{{ $comment->body }}</p>
            <p class="muted">{{ $comment->user?->name }}</p>
        </article>
    @endforeach
</section>
@endif
</section>
@endsection
