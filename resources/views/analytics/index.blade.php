@extends('layouts.app')
@section('title', __('Analytics'))
@section('content')
<div class="page-heading">
    <h1>{{ __('Editorial analytics') }}</h1>
    <a href="{{ route('analytics.export') }}">{{ __('Export CSV') }}</a>
</div>
<section class="card card-body">
    <h2>{{ __('Content overview') }}</h2>
    <p>{{ __('Total articles') }}: {{ $totalPosts }}</p>
    <ul>
        @foreach ($statusCounts as $status => $total)
            <li>{{ ucfirst($status) }}: {{ $total }}</li>
        @endforeach
    </ul>
</section>
<section class="card card-body">
    <h2>{{ __('Views by day') }}</h2>
    <ul>
        @forelse ($dailyViews as $day)
            <li>{{ $day->date }}: {{ $day->total }}</li>
        @empty
            <li>{{ __('No viewing data yet.') }}</li>
        @endforelse
    </ul>
</section>
<section class="card card-body">
    <h2>{{ __('Articles by views') }}</h2>
    @foreach ($posts as $post)
        <p><a href="{{ route('posts.edit', $post) }}">{{ $post->title }}</a> — {{ $post->views_count }}</p>
    @endforeach
    {{ $posts->links() }}
</section>
@endsection
