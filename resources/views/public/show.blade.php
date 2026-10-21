@extends('layouts.app')
@section('title', $post->title)
@section('content')
<article class="card card-body">
    <a href="{{ route('publication.index') }}">← {{ __('All articles') }}</a>
    <h1>{{ $post->title }}</h1>
    <p class="muted">{{ $post->author?->name }} · {{ $post->published_at?->format('Y-m-d') }}</p>
    <div class="article-body">{!! $post->renderedBody() !!}</div>
</article>
@if ($comments->isNotEmpty())
<section aria-labelledby="comments-heading">
    <h2 id="comments-heading">{{ __('Comments') }}</h2>
    @foreach ($comments as $comment)
        <article class="card card-body">
            <p>{{ $comment->body }}</p>
            <p class="muted">{{ $comment->user?->name }}</p>
        </article>
    @endforeach
</section>
@endif
@endsection
