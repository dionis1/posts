<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Media::class);
        $media = Media::query()
            ->when(! in_array($request->user()->role, [Role::Admin, Role::Editor], true), fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest()->paginate(18);

        return view('media.index', compact('media'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Media::class);
        $request->validate(['image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=32,min_height=32,max_width=6000,max_height=6000']]);
        $file = $request->file('image');
        $dimensions = getimagesize($file->getRealPath());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 20000000) {
            throw ValidationException::withMessages(['image' => __('The image must contain no more than 20 million pixels.')]);
        }
        $path = $file->store('media/incoming', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['image' => __('The image could not be stored. Please try again.')]);
        }
        $media = Media::create([
            'user_id' => $request->user()->id,
            'path' => $path,
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'status' => 'pending',
        ]);
        ProcessMedia::dispatch($media->id)->afterCommit();

        return back()->with('status', __('Image uploaded. Processing may take a moment.'));
    }

    public function show(Request $request, Media $media)
    {
        Gate::authorize('view', $media);
        $path = $request->boolean('thumbnail') ? $media->thumbnail_path : $media->path;
        abort_unless($path && Storage::disk($media->disk)->exists($path), 404);

        return Storage::disk($media->disk)->response($path, 'image.png', [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function destroy(Media $media)
    {
        Gate::authorize('delete', $media);
        if (Post::query()->where('cover_media_id', $media->id)->exists()) {
            throw ValidationException::withMessages(['image' => __('Remove this image from its posts before deleting it.')]);
        }
        $disk = $media->disk;
        $paths = array_filter([$media->path, $media->thumbnail_path]);
        $media->delete();
        Storage::disk($disk)->delete($paths);

        return back()->with('status', __('Image deleted.'));
    }
}
