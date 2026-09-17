<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource->toArray();
        if (! $request->is('api/v1/admin/*')) {
            unset($data['uploaded_by'], $data['author_id'], $data['deleted_at']);
            if (($data['category']['is_visible'] ?? true) === false) {
                $data['category'] = null;
            }
            if (isset($data['technologies'])) {
                $data['technologies'] = array_values(array_filter($data['technologies'], fn (array $technology): bool => ($technology['is_visible'] ?? true) !== false));
            }
            if (isset($data['project']) && (! $data['project']['published_at'] || strtotime($data['project']['published_at']) > time())) {
                $data['project'] = null;
            }
        }
        if (isset($data['body'])) {
            $data['reading_minutes'] = max(1, (int) ceil(str_word_count(strip_tags($data['body'])) / 200));
        }

        return $data;
    }
}
