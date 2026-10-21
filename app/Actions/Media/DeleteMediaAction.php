<?php

namespace App\Actions\Media;

use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class DeleteMediaAction
{
    public function __invoke(User $actor, Media $media): void
    {
        Gate::forUser($actor)->authorize('delete', $media);
        DB::transaction(function () use ($media) {
            $media = Media::lockForUpdate()->findOrFail($media->id);
            if (Post::withTrashed()->where('cover_media_id', $media->id)->exists()) {
                throw ValidationException::withMessages(['image' => __('Remove this image from its posts before deleting it.')]);
            }
            $disk = $media->disk;
            $paths = array_filter([$media->path, $media->thumbnail_path]);
            $media->delete();
            DB::afterCommit(fn () => Storage::disk($disk)->delete($paths));
        });
    }
}
