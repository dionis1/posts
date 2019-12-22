@extends('layouts.app')

@section('content')
<div class="container">
	<h4>{{ $post->title}}</h4>
	<p>Created {{ $post->created_at->toDateString()}}  Update {{ $post->updated_at->diffForHumans()}} </p>
	{!! $post->description !!}
	<p>Author : {{ $post->user->name}}</p>
</div>

@endsection