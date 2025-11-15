<?php
declare(strict_types=1);

namespace App\Modules\Task\Http\Controllers\Api;

use App\Modules\Task\Models\Tag;
use App\Modules\Task\Requests\TagRequest;
use App\Modules\Task\Resources\TagResource;
use App\Traits\CustomPaginates;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class TagController extends BaseController
{
    use CustomPaginates;
    public function __construct()
    {
        $this->middleware('auth:sanctum')->only(['index', 'store', 'update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $builder = Tag::orderBy('name');

        return TagResource::collection($this->applyPagination($builder, $request));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TagRequest $request): tagResource
    {
        $validated = $request->validated();
        $tag = Tag::create($validated);

        return new TagResource($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TagRequest $request, Tag $tag): TagResource
    {
        $validated = $request->validated();
        $tag->update($validated);

        return new TagResource($tag);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return response()->noContent();
    }
}
