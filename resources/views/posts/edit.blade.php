@extends('layouts.app')
@section('title',$post->exists?__('Edit article'):__('New article'))
@section('content')
<livewire:post-editor :post="$post" />
@endsection
