<?php

namespace App\Services;

use App\Models\Media;
use App\Support\ContentRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaService
{
    public function upload(UploadedFile $file, string $folder, ?string $alt, int $user): Media
    {
        if (mb_strlen($file->getClientOriginalName()) > 255) {
            throw ValidationException::withMessages(['file' => 'The file name must be at most 255 characters.']);
        }
        $disk = config('portfolio.media_disk');
        abort_unless(in_array($disk, ['public', 's3']), 503, 'Media storage is not configured.');
        $dimensions = str_starts_with($file->getMimeType(), 'image/') ? @getimagesize($file->getRealPath()) : null;
        if ($dimensions && ($dimensions[0] > 10000 || $dimensions[1] > 10000)) {
            throw ValidationException::withMessages(['file' => 'Images must be at most 10,000 pixels in each dimension.']);
        }
        $path = $file->store($folder, $disk);
        abort_unless($path, 503, 'The file could not be stored.');
        try {
            return Media::create(['disk' => $disk, 'path' => $path, 'folder' => $folder, 'original_name' => basename($file->getClientOriginalName()), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null, 'alt_text' => $alt, 'uploaded_by' => $user]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }

    public function delete(Media $media): void
    {
        foreach (ContentRegistry::all() as $definition) {
            foreach ($definition['fields'] as $name => $field) {
                if (($field['reference'] ?? null) === 'media' && $field['type'] === 'reference' && DB::table($definition['table'])->where($name, $media->id)->exists()) {
                    throw ValidationException::withMessages(['media' => 'Remove this file from its content before deleting it.']);
                }
            }
        }
        foreach (['project_media', 'article_media'] as $table) {
            if (DB::table($table)->where('media_id', $media->id)->exists()) {
                throw ValidationException::withMessages(['media' => 'Remove this file from its gallery before deleting it.']);
            }
        }
        abort_unless(Storage::disk($media->disk)->delete($media->path), 503, 'The file could not be deleted.');
        $media->delete();
    }
}
