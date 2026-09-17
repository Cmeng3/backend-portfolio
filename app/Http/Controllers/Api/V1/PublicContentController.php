<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContentResource;
use App\Support\ContentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicContentController extends Controller
{
    private function published(string $resource): Builder
    {
        abort_if($resource === 'contact-messages', 404);
        $definition = ContentRegistry::get($resource);
        $query = ContentRegistry::query($resource)->with($definition['relations']);
        if (isset($definition['fields']['published_at'])) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }
        if (isset($definition['fields']['is_visible'])) {
            $query->where('is_visible', true);
        }
        if ($resource === 'skills') {
            $query->whereHas('category', fn (Builder $category) => $category->where('is_visible', true));
        }
        if ($resource === 'site-settings') {
            $query->where('is_public', true);
        }

        return $query;
    }

    public function index(Request $request, string $resource): mixed
    {
        $input = $request->validate(['search' => 'nullable|string|max:100', 'category' => 'nullable|string|max:255', 'technology' => 'nullable|string|max:255', 'tag' => 'nullable|string|max:255', 'featured' => 'nullable|boolean', 'per_page' => 'nullable|integer|min:1|max:100', 'page' => 'nullable|integer|min:1', 'sort' => 'nullable|in:newest,oldest,title,order']);
        $definition = ContentRegistry::get($resource);
        $query = $this->published($resource);
        $title = isset($definition['fields']['title']) ? 'title' : (isset($definition['fields']['name']) ? 'name' : null);
        if (! empty($input['search']) && $title) {
            $query->whereLike($title, '%'.$input['search'].'%');
        }
        foreach (['category' => 'category', 'technology' => 'technologies', 'tag' => 'tags'] as $filter => $relation) {
            if (! empty($input[$filter]) && in_array($relation, $definition['relations'])) {
                $query->whereHas($relation, function (Builder $q) use ($filter, $input) {
                    $q->where('slug', $input[$filter]);
                    if (in_array($q->getModel()->getTable(), ['project_categories', 'technologies'])) {
                        $q->where('is_visible', true);
                    }
                });
            }
        }
        if ($request->boolean('featured') && isset($definition['fields']['is_featured'])) {
            $query->where('is_featured', true);
        }
        $sort = $input['sort'] ?? (isset($definition['fields']['sort_order']) ? 'order' : 'newest');
        if ($resource === 'resumes' && ! isset($input['sort'])) {
            $query->orderByDesc('updated_at');
        } elseif ($sort === 'order' && isset($definition['fields']['sort_order'])) {
            $query->orderBy('sort_order');
        } elseif ($sort === 'title' && $title) {
            $query->orderBy($title);
        } else {
            $query->orderBy(isset($definition['fields']['published_at']) ? 'published_at' : 'created_at', $sort === 'oldest' ? 'asc' : 'desc');
        }

        return ContentResource::collection($query->orderBy('id')->paginate($input['per_page'] ?? 12)->withQueryString());
    }

    public function show(string $resource, string $slug): ContentResource
    {
        abort_unless(in_array($resource, ['projects', 'blog']), 404);

        return new ContentResource($this->published($resource)->where('slug', $slug)->firstOrFail());
    }

    public function featured(Request $request): mixed
    {
        $request->merge(['featured' => 1]);

        return $this->index($request, 'projects');
    }
}
