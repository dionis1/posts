@extends('layouts.app')
@section('title',__('All content'))
@section('content')
<div class="page-heading"><div><p class="eyebrow">{{ __('YOUR EDITORIAL DESK') }}</p><h1>{{ __('Good stories start here.') }}</h1><p class="muted">{{ __('Bring your ideas to life, one thoughtful article at a time.') }}</p></div>@can('create',App\Models\Post::class)<a class="button primary" href="{{ route('posts.create') }}">＋ {{ __('New article') }}</a>@endcan</div>
<livewire:editorial-stats />
<livewire:post-library />
@endsection
