<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadMediaRequest;
use App\Http\Resources\ContentResource;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request): mixed
    {
        $input = $request->validate(['page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100']);

        return ContentResource::collection(Media::latest()->paginate($input['per_page'] ?? 25));
    }

    public function store(UploadMediaRequest $request, MediaService $service): mixed
    {
        return (new ContentResource($service->upload($request->file('file'), $request->validated('folder'), $request->validated('alt_text'), $request->user()->id)))->response()->setStatusCode(201);
    }

    public function update(Request $request, Media $media): ContentResource
    {
        $media->update($request->validate(['alt_text' => 'nullable|string|max:255']));

        return new ContentResource($media);
    }

    public function destroy(Media $media, MediaService $service): mixed
    {
        $service->delete($media);

        return response()->noContent();
    }
}
