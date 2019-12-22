@extends('layouts.app')

@section('content')
<editpost-component 
        post-id="{{ $post->id }}"
        title="{{ $post->title }}" 
        description="{!! $post->description !!}" >
</editpost-component>
@endsection