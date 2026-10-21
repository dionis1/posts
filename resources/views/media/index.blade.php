@extends('layouts.app')
@section('title', __('Media library'))
@section('content')
<div class="page-heading">
    <h1>{{ __('Media library') }}</h1>
    <p class="muted">{{ __('Uploads are checked and processed before use.') }}</p>
</div>
@can('create', App\Models\Media::class)
<form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" class="card card-body">
    @csrf
    <label for="image">{{ __('Upload image') }}</label>
    <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" required>
    <button type="submit">{{ __('Upload') }}</button>
</form>
@endcan
<div class="media-grid">
    @forelse ($media as $image)
        <article class="card card-body">
            <h2>{{ $image->original_name }}</h2>
            <p class="muted">{{ ucfirst($image->status->value) }}</p>
            @if ($image->status === \App\Enums\MediaStatus::Ready)
                <img loading="lazy" src="{{ route('media.show', $image) }}?thumbnail=1" alt="{{ $image->original_name }}" width="240">
            @endif
            @can('delete', $image)
                <form method="POST" action="{{ route('media.destroy', $image) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit">{{ __('Delete image') }}</button>
                </form>
            @endcan
        </article>
    @empty
        <p class="muted">{{ __('No images uploaded yet.') }}</p>
    @endforelse
</div>
{{ $media->links() }}
@endsection
