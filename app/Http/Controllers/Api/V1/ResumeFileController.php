<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Support\Facades\Storage;

class ResumeFileController extends Controller
{
    public function preview(Resume $resume): mixed
    {
        return $this->file($resume, 'inline');
    }

    public function download(Resume $resume): mixed
    {
        return $this->file($resume, 'attachment');
    }

    private function file(Resume $resume, string $disposition): mixed
    {
        abort_unless($resume->is_visible, 404);
        $media = $resume->media;
        abort_unless($media && $media->mime_type === 'application/pdf', 404);
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, $media->original_name, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $disposition);
    }
}
