@extends('layouts.app')
@section('title', __('Published articles'))
@section('content')
<div class="page-heading">
    <div>
        <p class="eyebrow">{{ __('THE PUBLICATION') }}</p>
        <h1>{{ __('Stories worth sharing.') }}</h1>
        <p class="muted">{{ __('Browse published articles from our editorial team.') }}</p>
    </div>
</div>
@forelse ($posts as $post)
    <article class="card card-body">
        <h2><a href="{{ route('publication.show', $post->slug) }}">{{ $post->title }}</a></h2>
        <p class="muted">{{ $post->author?->name }} · {{ $post->published_at?->format('Y-m-d') }}</p>
        <p>{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->body), 240) }}</p>
        <a href="{{ route('publication.show', $post->slug) }}">{{ __('Read article') }} →</a>
    </article>
@empty
    <p class="muted">{{ __('No articles have been published yet.') }}</p>
@endforelse
{{ $posts->links() }}
@endsection
