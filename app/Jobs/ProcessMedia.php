<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessMedia implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    public function __construct(public int $mediaId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('media:'.$this->mediaId))->releaseAfter(10)->expireAfter(120)];
    }

    public function handle(): void
    {
        $media = Media::find($this->mediaId);
        if (! $media || $media->status === 'ready') {
            return;
        }
        $disk = Storage::disk($media->disk);
        $raw = $disk->get($media->path);
        $image = $raw === null ? false : @imagecreatefromstring($raw);
        if ($image === false) {
            throw new RuntimeException('Unable to decode uploaded raster image.');
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = min(1, 480 / max($width, $height));
        $thumb = imagecreatetruecolor(max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagesavealpha($image, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, imagesx($thumb), imagesy($thumb), $width, $height);
        $path = 'media/processed/'.$media->id.'.png';
        $thumbnailPath = 'media/processed/'.$media->id.'-thumb.png';
        try {
            ob_start();
            imagepng($image, null, 6);
            $output = ob_get_clean();
            ob_start();
            imagepng($thumb, null, 6);
            $thumbnail = ob_get_clean();
            if (! $disk->put($path, $output) || ! $disk->put($thumbnailPath, $thumbnail)) {
                throw new RuntimeException('Unable to store processed image.');
            }
            $originalPath = $media->path;
            // The row can be deleted while processing. Avoid leaving files behind.
            $updated = Media::whereKey($media->id)->where('status', '!=', 'ready')->update([
                'path' => $path, 'thumbnail_path' => $thumbnailPath, 'mime' => 'image/png',
                'size' => strlen($output), 'width' => $width, 'height' => $height, 'status' => 'ready',
            ]);
            if (! $updated && ! Media::whereKey($media->id)->exists()) {
                $disk->delete([$path, $thumbnailPath]);
            }
            if ($originalPath !== $path) {
                $disk->delete($originalPath);
            }
        } finally {
            imagedestroy($image);
            imagedestroy($thumb);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Media::whereKey($this->mediaId)->where('status', '!=', 'ready')->update(['status' => 'failed']);
    }
}
