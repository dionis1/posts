@extends('layouts.app')
@section('title', __('Categories and tags'))
@section('content')
<div class="page-heading"><h1>{{ __('Categories and tags') }}</h1></div>
@foreach (['categories' => $categories, 'tags' => $tags] as $type => $items)
<section class="card card-body">
    <h2>{{ __(ucfirst($type)) }}</h2>
    <form method="POST" action="{{ route('taxonomy.store', $type) }}">
        @csrf
        <label for="new-{{ $type }}">{{ __('Name') }}</label>
        <input id="new-{{ $type }}" name="name" maxlength="100" required>
        <button type="submit">{{ __('Add') }}</button>
    </form>
    <ul>
        @forelse ($items as $item)
            <li>
                {{ $item->name }}
                <form method="POST" action="{{ route('taxonomy.destroy', [$type, $item->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit">{{ __('Delete') }}</button>
                </form>
            </li>
        @empty
            <li>{{ __('None yet.') }}</li>
        @endforelse
    </ul>
</section>
@endforeach
@endsection
