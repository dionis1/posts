@extends('layouts.app')
@section('title',__('Activity history'))
@section('content')<a href="{{ route('posts.edit',$post) }}">← {{ $post->title }}</a><h1>{{ __('Activity history') }}</h1><section class="card card-body"><ol class="timeline">@foreach($activities as $activity)<li><strong>{{ __($activity->event) }}</strong><span>{{ $activity->actor?->name??__('System') }} · {{ $activity->created_at->format('d M Y H:i') }} UTC</span><pre>{{ json_encode($activity->properties,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></li>@endforeach</ol>{{ $activities->links() }}</section>@endsection
