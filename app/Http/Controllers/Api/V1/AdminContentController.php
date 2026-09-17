<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Http\Resources\ContentResource;
use App\Support\ContentRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminContentController extends Controller
{
    public function schema(): array
    {
        return ['data' => ContentRegistry::all()];
    }

    public function index(Request $request, string $resource): mixed
    {
        $input = $request->validate(['page' => 'nullable|integer|min:1', 'per_page' => 'nullable|integer|min:1|max:100', 'search' => 'nullable|string|max:100']);
        $definition = ContentRegistry::get($resource);
        $query = ContentRegistry::query($resource)->with($definition['relations']);
        $field = isset($definition['fields']['title']) ? 'title' : (isset($definition['fields']['name']) ? 'name' : null);
        if ($field && ! empty($input['search'])) {
            $query->whereLike($field, '%'.$input['search'].'%');
        }

        return ContentResource::collection($query->latest('updated_at')->orderByDesc('id')->paginate($input['per_page'] ?? 25));
    }

    public function show(string $resource, int $id): ContentResource
    {
        return new ContentResource(ContentRegistry::query($resource)->with(ContentRegistry::get($resource)['relations'])->findOrFail($id));
    }

    private function save(ContentRequest $request, string $resource, ?int $id): ContentResource
    {
        $definition = ContentRegistry::get($resource);
        $record = DB::transaction(function () use ($request, $resource, $id, $definition): Model {
            $record = $id ? ContentRegistry::query($resource)->findOrFail($id) : ContentRegistry::query($resource)->make();
            $values = $request->validated();
            $relations = [];
            foreach ($definition['fields'] as $field => $spec) {
                if (isset($spec['relation']) && array_key_exists($field, $values)) {
                    $relations[$spec['relation']] = $values[$field];
                    unset($values[$field]);
                }
            }
            if (isset($definition['type'])) {
                $values['type'] = $definition['type'];
                if (! $id) {
                    $values['author_id'] = $request->user()->id;
                }
            }
            $record->fill($values)->save();
            foreach ($relations as $relation => $ids) {
                if ($relation === 'gallery') {
                    $ordered = [];
                    foreach ($ids as $order => $mediaId) {
                        $ordered[$mediaId] = ['sort_order' => $order];
                    }
                    $record->$relation()->sync($ordered);
                } else {
                    $record->$relation()->sync($ids);
                }
            }

            return $record;
        });

        return new ContentResource($record->load($definition['relations']));
    }

    public function store(ContentRequest $request, string $resource): mixed
    {
        return $this->save($request, $resource, null)->response()->setStatusCode(201);
    }

    public function update(ContentRequest $request, string $resource, int $id): ContentResource
    {
        return $this->save($request, $resource, $id);
    }

    public function destroy(string $resource, int $id): mixed
    {
        ContentRegistry::query($resource)->findOrFail($id)->delete();

        return response()->noContent();
    }
}
