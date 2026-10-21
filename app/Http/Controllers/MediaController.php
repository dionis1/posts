<?php

namespace App\Http\Controllers;

use App\Actions\Media\DeleteMediaAction;
use App\Actions\Media\UploadMediaAction;
use App\Enums\Role;
use App\Http\Requests\UploadMediaRequest;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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

    public function store(UploadMediaRequest $request, UploadMediaAction $upload)
    {
        $upload($request->user(), $request->file('image'));

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

    public function destroy(Request $request, Media $media, DeleteMediaAction $delete)
    {
        $delete($request->user(), $media);

        return back()->with('status', __('Image deleted.'));
    }
}
