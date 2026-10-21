<?php

namespace App\Actions\Media;

use App\Enums\MediaStatus;
use App\Jobs\ProcessMedia;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class UploadMediaAction
{
    public function __invoke(User $actor, UploadedFile $file): Media
    {
        Gate::forUser($actor)->authorize('create', Media::class);
        $dimensions = getimagesize($file->getRealPath());
        if (! $dimensions || $dimensions[0] * $dimensions[1] > 20000000) {
            throw ValidationException::withMessages(['image' => __('The image must contain no more than 20 million pixels.')]);
        }
        $path = $file->store('media/incoming', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['image' => __('The image could not be stored. Please try again.')]);
        }
        try {
            return DB::transaction(function () use ($actor, $file, $dimensions, $path) {
                $media = Media::create(['user_id' => $actor->id, 'path' => $path, 'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'mime' => $file->getMimeType(), 'size' => $file->getSize(), 'width' => $dimensions[0], 'height' => $dimensions[1], 'status' => MediaStatus::Pending]);
                ProcessMedia::dispatch($media->id)->afterCommit();

                return $media;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
    }
}
